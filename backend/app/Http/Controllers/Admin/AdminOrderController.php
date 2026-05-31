<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Order\StoreCourierApproachRoute;
use App\Actions\Order\TransitionOrderStatus;
use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderRouteSegment;
use App\Services\Logistics\CourierPayoutCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    public function index(Request $request): mixed
    {
        $perPage = min($request->integer('per_page', 20), 100);
        $query = $this->baseQuery()->orderByDesc('created_at');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('id', is_numeric($search) ? (int) $search : 0)
                    ->orWhereHas('user', fn ($query) => $query
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('restaurant', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%"));
            });
        }

        foreach (['status', 'payment_status', 'payment_method'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->get($field));
            }
        }

        foreach (['restaurant_id', 'courier_id', 'user_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->integer($field));
            }
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')?->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')?->endOfDay());
        }

        return OrderResource::collection($query->paginate($perPage));
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($this->loadOrder($order));
    }

    public function accept(Request $request, Order $order, TransitionOrderStatus $transitionOrderStatus): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::CREATED]);
        if ($order->payment_status !== PaymentStatus::PAID->value) {
            abort(422, 'Заказ еще не оплачен.');
        }

        return new OrderResource(DB::transaction(function () use ($request, $order, $transitionOrderStatus) {
            $transitionOrderStatus($order, OrderStatus::ACCEPTED_BY_RESTAURANT, $this->adminEventPayload($request));

            return $this->loadOrder($order);
        }));
    }

    public function cancel(Request $request, Order $order, TransitionOrderStatus $transitionOrderStatus): OrderResource
    {
        if ($this->isFinal($order)) {
            abort(422, 'Завершенный заказ нельзя отменить.');
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return new OrderResource(DB::transaction(function () use ($request, $order, $data, $transitionOrderStatus) {
            $transitionOrderStatus(
                $order,
                OrderStatus::CANCELED_BY_RESTAURANT,
                $this->adminEventPayload($request, [
                    'reason' => $data['reason'] ?? null,
                ]),
            );

            return $this->loadOrder($order);
        }));
    }

    public function ready(Request $request, Order $order, TransitionOrderStatus $transitionOrderStatus): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::ACCEPTED_BY_RESTAURANT]);

        return new OrderResource(DB::transaction(function () use ($request, $order, $transitionOrderStatus) {
            $transitionOrderStatus($order, OrderStatus::READY_FOR_PICKUP, $this->adminEventPayload($request));

            return $this->loadOrder($order);
        }));
    }

    public function assign(
        Request $request,
        Order $order,
        StoreCourierApproachRoute $storeCourierApproachRoute,
        TransitionOrderStatus $transitionOrderStatus,
    ): OrderResource {
        $data = $request->validate([
            'courier_user_id' => ['required', 'integer', 'exists:courier_profiles,user_id'],
        ]);

        $profile = CourierProfile::query()->findOrFail($data['courier_user_id']);
        $this->ensureCourierAvailable($profile);

        return new OrderResource(DB::transaction(function () use ($request, $order, $profile, $storeCourierApproachRoute, $transitionOrderStatus) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStatus($lockedOrder, [OrderStatus::READY_FOR_PICKUP]);

            if ($lockedOrder->courier_id !== null) {
                abort(422, 'У заказа уже назначен курьер.');
            }

            $transitionOrderStatus(
                $lockedOrder,
                OrderStatus::COURIER_ASSIGNED,
                $this->adminEventPayload($request, [
                    'courier_user_id' => $profile->user_id,
                ]),
                [
                    'courier_id' => $profile->user_id,
                ],
            );
            $storeCourierApproachRoute($lockedOrder, $profile);

            return $this->loadOrder($lockedOrder);
        }));
    }

    public function unassign(Request $request, Order $order, TransitionOrderStatus $transitionOrderStatus): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::COURIER_ASSIGNED]);

        return new OrderResource(DB::transaction(function () use ($request, $order, $transitionOrderStatus) {
            $previousCourierId = $order->courier_id;
            $transitionOrderStatus(
                $order,
                OrderStatus::READY_FOR_PICKUP,
                $this->adminEventPayload($request, [
                    'previous_courier_user_id' => $previousCourierId,
                    'admin_action' => 'unassign_courier',
                ]),
                [
                    'courier_id' => null,
                ],
            );
            OrderRouteSegment::query()
                ->where('order_id', $order->id)
                ->where('segment_type', 'courier_to_restaurant')
                ->delete();

            return $this->loadOrder($order);
        }));
    }

    public function pickedUp(Request $request, Order $order, TransitionOrderStatus $transitionOrderStatus): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::COURIER_ASSIGNED]);
        if ($order->courier_id === null) {
            abort(422, 'Перед выдачей нужно назначить курьера.');
        }

        return new OrderResource(DB::transaction(function () use ($request, $order, $transitionOrderStatus) {
            $transitionOrderStatus(
                $order,
                OrderStatus::PICKED_UP,
                $this->adminEventPayload($request, [
                    'courier_user_id' => $order->courier_id,
                ]),
            );

            return $this->loadOrder($order);
        }));
    }

    public function delivered(
        Request $request,
        Order $order,
        CourierPayoutCalculator $payouts,
        TransitionOrderStatus $transitionOrderStatus,
    ): OrderResource {
        $this->ensureStatus($order, [OrderStatus::PICKED_UP]);
        if ($order->courier_id === null) {
            abort(422, 'Перед доставкой нужно назначить курьера.');
        }

        return new OrderResource(DB::transaction(function () use ($request, $order, $payouts, $transitionOrderStatus) {
            $courierFee = $payouts->calculate($order);
            $transitionOrderStatus(
                $order,
                OrderStatus::DELIVERED,
                $this->adminEventPayload($request, [
                    'courier_user_id' => $order->courier_id,
                    'courier_fee' => $courierFee,
                ]),
                [
                    'courier_fee' => $courierFee,
                ],
            );

            return $this->loadOrder($order);
        }));
    }

    public function updatePayment(Request $request, Order $order): OrderResource
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(['PENDING', 'AUTHORIZED', 'PAID', 'REFUNDED', 'FAILED'])],
        ]);

        return new OrderResource(DB::transaction(function () use ($request, $order, $data) {
            $before = $order->payment_status;
            $order->payment_status = $data['payment_status'];
            $order->save();
            $this->event($request, $order, $order->status, [
                'admin_action' => 'payment_status_update',
                'before' => $before,
                'after' => $order->payment_status,
            ]);

            return $this->loadOrder($order);
        }));
    }

    private function baseQuery()
    {
        return Order::query()->with([
            'user:id,name,email,phone,is_banned',
            'courier.user:id,name,email,phone',
            'restaurant.address',
            'items.product.images.media',
            'events',
            'deliveryAddress',
            'routeSegments',
        ]);
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load([
            'user:id,name,email,phone,is_banned',
            'courier.user:id,name,email,phone',
            'restaurant.address',
            'items.product.images.media',
            'events',
            'deliveryAddress',
            'routeSegments',
        ]);
    }

    /**
     * @param  array<int, OrderStatus>  $statuses
     */
    private function ensureStatus(Order $order, array $statuses): void
    {
        $allowed = array_map(fn (OrderStatus $status) => $status->value, $statuses);
        if (! in_array($order->status, $allowed, true)) {
            abort(422, 'Недопустимый статус заказа для этого действия.');
        }
    }

    private function isFinal(Order $order): bool
    {
        return in_array($order->status, [
            OrderStatus::DELIVERED->value,
            OrderStatus::CANCELED_BY_USER->value,
            OrderStatus::CANCELED_BY_RESTAURANT->value,
        ], true);
    }

    private function ensureCourierAvailable(CourierProfile $profile): void
    {
        if ($profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(422, 'Курьер неактивен.');
        }

        $hasOpenShift = CourierShift::query()
            ->where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->exists();

        if (! $hasOpenShift) {
            abort(422, 'У курьера нет открытой смены.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function event(Request $request, Order $order, string $event, array $payload = []): void
    {
        OrderEvent::create([
            'order_id' => $order->id,
            'event' => $event,
            'payload' => $this->adminEventPayload($request, $payload),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function adminEventPayload(Request $request, array $payload = []): array
    {
        return array_merge($payload, [
            'by_admin_user_id' => $request->user()->id,
        ]);
    }
}
