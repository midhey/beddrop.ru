<?php

namespace App\Services\Logistics;

use App\Models\Restaurant;

class DeliveryTimeCalculator
{
    public function __construct(
        private readonly LogisticsSettingsService $settings,
    ) {
    }

    /**
     * @return array{prep: int, pickup_buffer: int, delivery: int, buffer: int, total: int}
     */
    public function calculate(Restaurant $restaurant, int|float $deliverySeconds): array
    {
        $prepTime = $this->restaurantPrepTime($restaurant);
        $pickupBuffer = (int) $this->settings->get('delivery.pickup_buffer_time_min', 3);
        $bufferTime = (int) $this->settings->get('delivery.buffer_time_min', 5);
        $deliveryMinutes = (int) ceil(((float) $deliverySeconds) / 60);

        return [
            'prep' => $prepTime,
            'pickup_buffer' => $pickupBuffer,
            'delivery' => $deliveryMinutes,
            'buffer' => $bufferTime,
            'total' => $prepTime + $pickupBuffer + $deliveryMinutes + $bufferTime,
        ];
    }

    public function restaurantPrepTime(Restaurant $restaurant): int
    {
        if ($restaurant->prepTimeAverageMinutes() !== null) {
            return $restaurant->prepTimeAverageMinutes();
        }

        return (int) $this->settings->get('delivery.default_prep_time_min', 20);
    }
}
