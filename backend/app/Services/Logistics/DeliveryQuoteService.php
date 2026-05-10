<?php

namespace App\Services\Logistics;

use App\Models\Address;
use App\Models\Restaurant;
use RuntimeException;

class DeliveryQuoteService
{
    public function __construct(
        private readonly ValhallaRouteService $routes,
        private readonly DeliveryPriceCalculator $prices,
        private readonly DeliveryTimeCalculator $times,
        private readonly LogisticsSettingsService $settings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function quote(Restaurant $restaurant, Address $deliveryAddress, string $mode = 'auto'): array
    {
        $restaurant->loadMissing('address');

        if (!$restaurant->address) {
            throw new RuntimeException('У ресторана не указан адрес.');
        }

        $this->ensureCoordinates($restaurant->address, 'У ресторана нет координат.');
        $this->ensureCoordinates($deliveryAddress, 'У адреса доставки нет координат.');

        $route = $this->routes->route(
            ['lat' => $restaurant->address->lat, 'lng' => $restaurant->address->lng],
            ['lat' => $deliveryAddress->lat, 'lng' => $deliveryAddress->lng],
            $mode,
        );

        $price = $this->prices->calculate($route['distance_meters']);
        $time = $this->times->calculate($restaurant, $route['duration_seconds']);

        return [
            'restaurant_id' => $restaurant->id,
            'delivery_address_id' => $deliveryAddress->id,
            'mode' => $mode,
            'distance_meters' => $route['distance_meters'],
            'duration_seconds' => $route['duration_seconds'],
            'prep_time_minutes' => $time['prep'],
            'eta_minutes' => $time['total'],
            'price' => $price,
            'delivery_price' => $price['total'],
            'time' => $time,
            'route' => $route,
            'settings_snapshot' => $this->settings->snapshot(),
        ];
    }

    private function ensureCoordinates(Address $address, string $message): void
    {
        if ($address->lat === null || $address->lng === null) {
            throw new RuntimeException($message);
        }
    }
}
