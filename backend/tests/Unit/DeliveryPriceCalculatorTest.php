<?php

namespace Tests\Unit;

use App\Models\LogisticsSetting;
use App\Services\Logistics\DeliveryPriceCalculator;
use App\Services\Logistics\LogisticsSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_price_calculator_adds_base_distance_and_service_fee(): void
    {
        $this->setLogisticsSetting('delivery.base_price', '125.50');
        $this->setLogisticsSetting('delivery.price_per_km', '40');
        $this->setLogisticsSetting('delivery.service_fee', '24.50');

        $price = $this->calculator()->calculate(2500);

        $this->assertSame(125.50, $price['base']);
        $this->assertSame(100.00, $price['distance']);
        $this->assertSame(24.50, $price['service']);
        $this->assertSame(250.00, $price['total']);
    }

    public function test_delivery_price_calculator_handles_zero_distance(): void
    {
        $this->setLogisticsSetting('delivery.base_price', '100');
        $this->setLogisticsSetting('delivery.price_per_km', '99');
        $this->setLogisticsSetting('delivery.service_fee', '15');

        $price = $this->calculator()->calculate(0);

        $this->assertSame(100.00, $price['base']);
        $this->assertSame(0.00, $price['distance']);
        $this->assertSame(15.00, $price['service']);
        $this->assertSame(115.00, $price['total']);
    }

    public function test_delivery_price_calculator_rounds_distance_and_total_to_two_decimals(): void
    {
        $this->setLogisticsSetting('delivery.base_price', '100');
        $this->setLogisticsSetting('delivery.price_per_km', '9.99');
        $this->setLogisticsSetting('delivery.service_fee', '5.55');

        $price = $this->calculator()->calculate(1234);

        $this->assertSame(12.33, $price['distance']);
        $this->assertSame(117.88, $price['total']);
    }

    public function test_delivery_price_calculator_uses_fallback_defaults_when_settings_are_missing(): void
    {
        LogisticsSetting::query()
            ->whereIn('key', [
                'delivery.base_price',
                'delivery.price_per_km',
                'delivery.service_fee',
            ])
            ->delete();

        $price = $this->calculator()->calculate(2000);

        $this->assertSame(149.00, $price['base']);
        $this->assertSame(60.00, $price['distance']);
        $this->assertSame(39.00, $price['service']);
        $this->assertSame(248.00, $price['total']);
    }

    private function calculator(): DeliveryPriceCalculator
    {
        return new DeliveryPriceCalculator(new LogisticsSettingsService());
    }

    private function setLogisticsSetting(string $key, string $value): void
    {
        LogisticsSetting::query()
            ->where('key', $key)
            ->update(['value' => $value]);
    }
}
