<?php

namespace App\Services\Logistics;

use App\Models\Order;

class CourierPayoutCalculator
{
    private const DEFAULT_COURIER_FEE = 150.00;
    private const DEFAULT_SERVICE_COMMISSION_PERCENT = 20.00;

    public function __construct(
        private readonly LogisticsSettingsService $settings,
    ) {
    }

    public function calculate(Order $order): float
    {
        $deliveryPrice = $order->delivery_price_snapshot;

        if ($deliveryPrice === null || (float) $deliveryPrice <= 0) {
            return self::DEFAULT_COURIER_FEE;
        }

        $commissionPercent = $this->commissionPercent($order);
        $courierPercent = max(0, 100 - $commissionPercent);

        return round((float) $deliveryPrice * $courierPercent / 100, 2);
    }

    private function commissionPercent(Order $order): float
    {
        $snapshotSettings = $order->logistics_snapshot_json['settings'] ?? [];

        return (float) (
            $snapshotSettings['delivery.service_commission_percent']
            ?? $this->settings->get(
                'delivery.service_commission_percent',
                self::DEFAULT_SERVICE_COMMISSION_PERCENT,
            )
        );
    }
}
