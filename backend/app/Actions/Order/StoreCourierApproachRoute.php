<?php

namespace App\Actions\Order;

use App\Models\CourierLocation;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderRouteSegment;
use App\Services\Logistics\LogisticsSettingsService;
use App\Services\Logistics\ValhallaRouteService;
use Throwable;

class StoreCourierApproachRoute
{
    public function __construct(
        private readonly ValhallaRouteService $routes,
        private readonly LogisticsSettingsService $settings,
    ) {}

    public function __invoke(Order $order, CourierProfile $profile): void
    {
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
