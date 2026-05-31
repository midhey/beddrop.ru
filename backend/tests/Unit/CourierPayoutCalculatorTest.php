<?php

namespace Tests\Unit;

use App\Models\LogisticsSetting;
use App\Models\Order;
use App\Services\Logistics\CourierPayoutCalculator;
use App\Services\Logistics\LogisticsSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierPayoutCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_payout_calculator_uses_delivery_price_snapshot_and_snapshot_commission(): void
    {
        $order = new Order([
            'delivery_price_snapshot' => 250,
            'logistics_snapshot_json' => [
                'settings' => [
                    'delivery.service_commission_percent' => 15,
                ],
            ],
        ]);

        $this->assertSame(212.50, $this->calculator()->calculate($order));
    }

    public function test_courier_payout_calculator_uses_current_commission_when_snapshot_setting_is_missing(): void
    {
        $this->setLogisticsSetting('delivery.service_commission_percent', '25');
        $order = new Order([
            'delivery_price_snapshot' => 200,
            'logistics_snapshot_json' => [
                'settings' => [],
            ],
        ]);

        $this->assertSame(150.00, $this->calculator()->calculate($order));
    }

    public function test_courier_payout_calculator_rounds_payout_to_two_decimals(): void
    {
        $order = new Order([
            'delivery_price_snapshot' => 199.99,
            'logistics_snapshot_json' => [
                'settings' => [
                    'delivery.service_commission_percent' => 12.5,
                ],
            ],
        ]);

        $this->assertSame(174.99, $this->calculator()->calculate($order));
    }

    public function test_courier_payout_calculator_uses_default_commission_when_setting_is_missing(): void
    {
        LogisticsSetting::query()
            ->where('key', 'delivery.service_commission_percent')
            ->delete();
        $order = new Order([
            'delivery_price_snapshot' => 200,
        ]);

        $this->assertSame(160.00, $this->calculator()->calculate($order));
    }

    public function test_courier_payout_calculator_returns_default_fee_without_positive_delivery_price(): void
    {
        $this->assertSame(150.00, $this->calculator()->calculate(new Order()));
        $this->assertSame(150.00, $this->calculator()->calculate(new Order([
            'delivery_price_snapshot' => 0,
        ])));
    }

    private function calculator(): CourierPayoutCalculator
    {
        return new CourierPayoutCalculator(new LogisticsSettingsService());
    }

    private function setLogisticsSetting(string $key, string $value): void
    {
        LogisticsSetting::query()
            ->where('key', $key)
            ->update(['value' => $value]);
    }
}
