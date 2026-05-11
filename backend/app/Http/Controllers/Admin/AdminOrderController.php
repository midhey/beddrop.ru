<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\CourierLocation;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderRouteSegment;
use App\Services\Logistics\CourierPayoutCalculator;
use App\Services\Logistics\LogisticsSettingsService;
use App\Services\Logistics\ValhallaRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

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

    public function accept(Request $request, Order $order): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::CREATED]);

        return new OrderResource(DB::transaction(function () use ($request, $order) {
            $order->status = OrderStatus::ACCEPTED_BY_RESTAURANT->value;
            $order->save();
            $this->event($request, $order, OrderStatus::ACCEPTED_BY_RESTAURANT->value);

            return $this->loadOrder($order);
        }));
    }

    public function cancel(Request $request, Order $order): OrderResource
    {
        if ($this->isFinal($order)) {
            abort(422, 'Завершенный заказ нельзя отменить.');
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return new OrderResource(DB::transaction(function () use ($request, $order, $data) {
            $order->status = OrderStatus::CANCELED_BY_RESTAURANT->value;
            $order->save();
            $this->event($request, $order, OrderStatus::CANCELED_BY_RESTAURANT->value, [
                'reason' => $data['reason'] ?? null,
            ]);

            return $this->loadOrder($order);
        }));
    }

    public function assign(
        Request $request,
        Order $order,
        ValhallaRouteService $routes,
        LogisticsSettingsService $settings,
    ): OrderResource {
        $data = $request->validate([
            'courier_user_id' => ['required', 'integer', 'exists:courier_profiles,user_id'],
        ]);

        $this->ensureStatus($order, [OrderStatus::ACCEPTED_BY_RESTAURANT]);

        if ($order->courier_id !== null) {
            abort(422, 'У заказа уже назначен курьер.');
        }

        $profile = CourierProfile::query()->findOrFail($data['courier_user_id']);
        $this->ensureCourierAvailable($profile);

        return new OrderResource(DB::transaction(function () use ($request, $order, $profile, $routes, $settings) {
            $order->courier_id = $profile->user_id;
            $order->status = OrderStatus::COURIER_ASSIGNED->value;
            $order->save();
            $this->event($request, $order, OrderStatus::COURIER_ASSIGNED->value, [
                'courier_user_id' => $profile->user_id,
            ]);
            $this->storeApproachRoute($order, $profile, $routes, $settings);

            return $this->loadOrder($order);
        }));
    }

    public function unassign(Request $request, Order $order): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::COURIER_ASSIGNED]);

        return new OrderResource(DB::transaction(function () use ($request, $order) {
            $previousCourierId = $order->courier_id;
            $order->courier_id = null;
            $order->status = OrderStatus::ACCEPTED_BY_RESTAURANT->value;
            $order->save();
            OrderRouteSegment::query()
                ->where('order_id', $order->id)
                ->where('segment_type', 'courier_to_restaurant')
                ->delete();
            $this->event($request, $order, OrderStatus::ACCEPTED_BY_RESTAURANT->value, [
                'previous_courier_user_id' => $previousCourierId,
                'admin_action' => 'unassign_courier',
            ]);

            return $this->loadOrder($order);
        }));
    }

    public function pickedUp(Request $request, Order $order): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::COURIER_ASSIGNED]);
        if ($order->courier_id === null) {
            abort(422, 'Перед выдачей нужно назначить курьера.');
        }

        return new OrderResource(DB::transaction(function () use ($request, $order) {
            $order->status = OrderStatus::PICKED_UP->value;
            $order->save();
            $this->event($request, $order, OrderStatus::PICKED_UP->value, [
                'courier_user_id' => $order->courier_id,
            ]);

            return $this->loadOrder($order);
        }));
    }

    public function delivered(Request $request, Order $order, CourierPayoutCalculator $payouts): OrderResource
    {
        $this->ensureStatus($order, [OrderStatus::PICKED_UP]);
        if ($order->courier_id === null) {
            abort(422, 'Перед доставкой нужно назначить курьера.');
        }

        return new OrderResource(DB::transaction(function () use ($request, $order, $payouts) {
            $order->status = OrderStatus::DELIVERED->value;
            $order->courier_fee = $payouts->calculate($order);
            $order->save();
            $this->event($request, $order, OrderStatus::DELIVERED->value, [
                'courier_user_id' => $order->courier_id,
                'courier_fee' => $order->courier_fee,
            ]);

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
     * @param array<int, OrderStatus> $statuses
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
     * @param array<string, mixed> $payload
     */
    private function event(Request $request, Order $order, string $event, array $payload = []): void
    {
        OrderEvent::create([
            'order_id' => $order->id,
            'event' => $event,
            'payload' => array_merge($payload, [
                'by_admin_user_id' => $request->user()->id,
            ]),
        ]);
    }

    private function storeApproachRoute(
        Order $order,
        CourierProfile $profile,
        ValhallaRouteService $routes,
        LogisticsSettingsService $settings,
    ): void {
        if (! config('services.valhalla.url')) {
            return;
        }

        $order->loadMissing('restaurant.address');
        $restaurantAddress = $order->restaurant?->address;
        $location = CourierLocation::query()
            ->where('courier_user_id', $profile->user_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->first();

        if (
            ! $location ||
            ! $restaurantAddress ||
            $restaurantAddress->lat === null ||
            $restaurantAddress->lng === null
        ) {
            return;
        }

        try {
            $route = $routes->route(
                ['lat' => $location->lat, 'lng' => $location->lng],
                ['lat' => $restaurantAddress->lat, 'lng' => $restaurantAddress->lng],
                $this->modeForVehicle($profile->vehicle),
            );
        } catch (Throwable) {
            return;
        }

        OrderRouteSegment::updateOrCreate(
            [
                'order_id' => $order->id,
                'segment_type' => 'courier_to_restaurant',
            ],
            [
                'mode' => $this->modeForVehicle($profile->vehicle),
                'distance_meters' => $route['distance_meters'],
                'duration_seconds' => $route['duration_seconds'],
                'encoded_shape' => $route['encoded_shape'],
                'raw_response_json' => $route['raw'],
                'settings_snapshot_json' => $settings->snapshot(),
            ],
        );
    }

    private function modeForVehicle(?string $vehicle): string
    {
        return match ($vehicle) {
            'FOOT' => 'pedestrian',
            'BIKE' => 'bicycle',
            default => 'auto',
        };
    }
}
