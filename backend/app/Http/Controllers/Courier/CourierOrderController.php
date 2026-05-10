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
use App\Models\CourierLocation;
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
        $profile = $this->ensureOpenShift($request);

        $orders = Order::query()
            ->where('status', OrderStatus::ACCEPTED_BY_RESTAURANT->value)
            ->whereNull('courier_id')
            ->with([
                'restaurant.address',
                'items.product',
                'deliveryAddress',
                'routeSegments',
            ])
            ->orderBy('created_at')
            ->paginate(20);

        $latestLocation = CourierLocation::query()
            ->where('courier_user_id', $profile->user_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->first();

        if ($latestLocation) {
            $orders->setCollection(
                $orders->getCollection()
                    ->map(function (Order $order) use ($latestLocation) {
                        $restaurantAddress = $order->restaurant?->address;

                        if ($restaurantAddress?->lat !== null && $restaurantAddress?->lng !== null) {
                            $order->setAttribute('courier_approach_distance_meters', $this->distanceMeters(
                                $latestLocation->lat,
                                $latestLocation->lng,
                                $restaurantAddress->lat,
                                $restaurantAddress->lng,
                            ));
                        }

                        return $order;
                    })
                    ->sortBy(fn (Order $order) => $order->getAttribute('courier_approach_distance_meters') ?? PHP_INT_MAX)
                    ->values()
            );
        }

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
                'routeSegments',
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
                'routeSegments',
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

    private function distanceMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): int
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
