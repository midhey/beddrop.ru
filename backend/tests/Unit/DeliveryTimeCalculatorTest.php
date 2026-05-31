<?php

namespace Tests\Unit;

use App\Models\LogisticsSetting;
use App\Models\Restaurant;
use App\Services\Logistics\DeliveryTimeCalculator;
use App\Services\Logistics\LogisticsSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTimeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_time_calculator_uses_prep_time_range_delivery_duration_and_buffers(): void
    {
        $this->setLogisticsSetting('delivery.pickup_buffer_time_min', '4');
        $this->setLogisticsSetting('delivery.buffer_time_min', '6');
        $restaurant = new Restaurant([
            'prep_time_min' => 10,
            'prep_time_max' => 21,
        ]);

        $time = $this->calculator()->calculate($restaurant, 121);

        $this->assertSame(16, $time['prep']);
        $this->assertSame(4, $time['pickup_buffer']);
        $this->assertSame(3, $time['delivery']);
        $this->assertSame(6, $time['buffer']);
        $this->assertSame(29, $time['total']);
    }

    public function test_delivery_time_calculator_uses_min_prep_time_when_max_is_missing(): void
    {
        $restaurant = new Restaurant([
            'prep_time_min' => 12,
            'prep_time_max' => null,
        ]);

        $this->assertSame(12, $this->calculator()->restaurantPrepTime($restaurant));
    }

    public function test_delivery_time_calculator_uses_default_prep_time_when_restaurant_has_no_prep_time(): void
    {
        $this->setLogisticsSetting('delivery.default_prep_time_min', '27');
        $restaurant = new Restaurant([
            'prep_time_min' => null,
            'prep_time_max' => null,
        ]);

        $this->assertSame(27, $this->calculator()->restaurantPrepTime($restaurant));
    }

    public function test_delivery_time_calculator_uses_fallback_defaults_when_settings_are_missing(): void
    {
        LogisticsSetting::query()
            ->whereIn('key', [
                'delivery.default_prep_time_min',
                'delivery.pickup_buffer_time_min',
                'delivery.buffer_time_min',
            ])
            ->delete();

        $restaurant = new Restaurant([
            'prep_time_min' => null,
            'prep_time_max' => null,
        ]);

        $time = $this->calculator()->calculate($restaurant, 60);

        $this->assertSame(20, $time['prep']);
        $this->assertSame(3, $time['pickup_buffer']);
        $this->assertSame(1, $time['delivery']);
        $this->assertSame(5, $time['buffer']);
        $this->assertSame(29, $time['total']);
    }

    private function calculator(): DeliveryTimeCalculator
    {
        return new DeliveryTimeCalculator(new LogisticsSettingsService());
    }

    private function setLogisticsSetting(string $key, string $value): void
    {
        LogisticsSetting::query()
            ->where('key', $key)
            ->update(['value' => $value]);
    }
}
