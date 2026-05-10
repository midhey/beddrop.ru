<?php

namespace App\Actions\Courier;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\CourierProfile;
use App\Models\CourierLocation;
use App\Models\CourierShift;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderRouteSegment;
use App\Models\User;
use App\Services\Logistics\LogisticsSettingsService;
use App\Services\Logistics\ValhallaRouteService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Throwable;

class AssignCourierOrder
{
    public function __construct(
        private readonly ValhallaRouteService $routes,
        private readonly LogisticsSettingsService $settings,
    ) {
    }

    public function __invoke(User $user, Order $order): Order
    {
        $profile = $this->ensureOpenShift($user);

        if (
            $order->status !== OrderStatus::ACCEPTED_BY_RESTAURANT->value ||
            $order->courier_id !== null
        ) {
            throw new HttpResponseException(response()->json([
                'message' => 'Этот заказ нельзя взять в работу',
            ], 422));
        }

        return DB::transaction(function () use ($order, $profile) {
            $order->courier_id = $profile->user_id;
            $order->status = OrderStatus::COURIER_ASSIGNED->value;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => OrderStatus::COURIER_ASSIGNED->value,
                'payload' => [
                    'courier_user_id' => $profile->user_id,
                ],
            ]);

            $this->storeApproachRoute($order, $profile);

            return $order->load([
                'restaurant.address',
                'items.product',
                'deliveryAddress',
                'routeSegments',
            ]);
        });
    }

    private function ensureOpenShift(User $user): CourierProfile
    {
        $profile = $user->courierProfile;

        if (! $profile || $profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        $hasOpenShift = CourierShift::query()
            ->where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->exists();

        if (! $hasOpenShift) {
            abort(422, 'У вас нет открытой смены.');
        }

        return $profile;
    }

    private function storeApproachRoute(Order $order, CourierProfile $profile): void
    {
        if (!config('services.valhalla.url')) {
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
            !$location ||
            !$restaurantAddress ||
            $restaurantAddress->lat === null ||
            $restaurantAddress->lng === null
        ) {
            return;
        }

        try {
            $route = $this->routes->route(
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
                'settings_snapshot_json' => $this->settings->snapshot(),
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
