<?php

namespace App\Http\Controllers\Courier;

use App\Actions\Courier\AssignCourierOrder;
use App\Actions\Courier\MarkOrderDelivered;
use App\Actions\Courier\MarkOrderPickedUp;
use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\CourierShift;
use App\Models\Order;
use Illuminate\Http\Request;

class CourierOrderController extends Controller
{
    private function ensureCourier(Request $request)
    {
        $profile = $request->user()->courierProfile;

        if (!$profile || $profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        return $profile;
    }

    private function ensureOpenShift(Request $request)
    {
        $profile = $this->ensureCourier($request);

        $hasOpenShift = CourierShift::query()
            ->where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->exists();

        if (! $hasOpenShift) {
            abort(422, 'У вас нет открытой смены.');
        }

        return $profile;
    }

    public function available(Request $request)
    {
        $this->ensureOpenShift($request);

        $orders = Order::query()
            ->where('status', OrderStatus::ACCEPTED_BY_RESTAURANT->value)
            ->whereNull('courier_id')
            ->with([
                'restaurant.address',
                'items.product',
                'deliveryAddress',
            ])
            ->orderBy('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function active(Request $request)
    {
        $profile = $this->ensureOpenShift($request);

        $orders = Order::query()
            ->where('courier_id', $profile->user_id)
            ->whereIn('status', [
                OrderStatus::COURIER_ASSIGNED->value,
                OrderStatus::PICKED_UP->value,
            ])
            ->with([
                'restaurant.address',
                'items.product',
                'deliveryAddress',
            ])
            ->orderBy('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function history(Request $request)
    {
        $profile = $this->ensureCourier($request);

        $orders = Order::query()
            ->where('courier_id', $profile->user_id)
            ->where('status', OrderStatus::DELIVERED->value)
            ->with([
                'restaurant.address',
                'items.product',
                'deliveryAddress',
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function assign(Request $request, Order $order, AssignCourierOrder $assignCourierOrder)
    {
        return new OrderResource($assignCourierOrder($request->user(), $order));
    }

    public function pickedUp(Request $request, Order $order, MarkOrderPickedUp $markOrderPickedUp)
    {
        return new OrderResource($markOrderPickedUp($request->user(), $order));
    }

    public function delivered(Request $request, Order $order, MarkOrderDelivered $markOrderDelivered)
    {
        return new OrderResource($markOrderDelivered($request->user(), $order));
    }
}
