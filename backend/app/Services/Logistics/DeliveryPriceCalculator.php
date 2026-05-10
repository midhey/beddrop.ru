<?php

namespace App\Services\Logistics;

class DeliveryPriceCalculator
{
    public function __construct(
        private readonly LogisticsSettingsService $settings,
    ) {
    }

    /**
     * @return array{base: float, distance: float, service: float, total: float}
     */
    public function calculate(int|float $distanceMeters): array
    {
        $basePrice = (float) $this->settings->get('delivery.base_price', 149);
        $pricePerKm = (float) $this->settings->get('delivery.price_per_km', 30);
        $serviceFee = (float) $this->settings->get('delivery.service_fee', 39);
        $distanceKm = ((float) $distanceMeters) / 1000;
        $distancePrice = round($distanceKm * $pricePerKm, 2);

        return [
            'base' => $basePrice,
            'distance' => $distancePrice,
            'service' => $serviceFee,
            'total' => round($basePrice + $distancePrice + $serviceFee, 2),
        ];
    }
}
