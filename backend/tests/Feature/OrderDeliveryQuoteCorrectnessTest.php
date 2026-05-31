<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderRouteSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class OrderDeliveryQuoteCorrectnessTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_delivery_address_is_required_for_order_creation(): void
    {
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $this->createProduct($restaurant));

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'payment_method' => PaymentMethod::ONLINE->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery_address_id');

        $this->assertSame(0, Order::query()->where('user_id', $customer->id)->count());
    }

    public function test_quote_failure_rejects_order_instead_of_creating_zero_delivery_price(): void
    {
        config(['services.valhalla.url' => 'https://valhalla.test']);

        Http::fake([
            'valhalla.test/route' => Http::response(['message' => 'route failed'], 500),
        ]);

        $customer = $this->createUser();
        $restaurant = $this->createRestaurant(null, [
            'address' => $this->createAddress(null, ['lat' => 58.52, 'lng' => 31.27]),
        ]);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $this->createProduct($restaurant));
        $address = $this->createAddress($customer, ['lat' => 58.53, 'lng' => 31.28]);

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'delivery_address_id' => $address->id,
                'payment_method' => PaymentMethod::ONLINE->value,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Не удалось рассчитать доставку.');

        $this->assertSame(0, Order::query()->where('user_id', $customer->id)->count());
    }

    public function test_successful_quote_creates_order_with_delivery_snapshot_and_route_segment(): void
    {
        config(['services.valhalla.url' => 'https://valhalla.test']);

        Http::fake([
            'valhalla.test/route' => Http::response([
                'trip' => [
                    'summary' => [
                        'length' => 4.2,
                        'time' => 780,
                    ],
                    'legs' => [
                        ['shape' => 'encoded-shape'],
                    ],
                ],
            ]),
        ]);

        $customer = $this->createUser();
        $restaurant = $this->createRestaurant(null, [
            'address' => $this->createAddress(null, ['lat' => 58.52, 'lng' => 31.27]),
        ]);
        $cart = $this->createActiveCart($customer, $restaurant);
        $this->addCartItem($cart, $this->createProduct($restaurant));
        $address = $this->createAddress($customer, ['lat' => 58.53, 'lng' => 31.28]);

        $this
            ->actingAs($customer, 'api')
            ->postJson('/api/v1/orders', [
                'delivery_address_id' => $address->id,
                'payment_method' => PaymentMethod::ONLINE->value,
            ])
            ->assertCreated()
            ->assertJsonPath('data.delivery_address_id', $address->id)
            ->assertJsonPath('data.delivery_distance_meters', 4200)
            ->assertJsonPath('data.delivery_duration_seconds', 780)
            ->assertJsonPath('data.delivery_price_snapshot', '314.00')
            ->assertJsonPath('data.route_segments.0.segment_type', 'restaurant_to_client')
            ->assertJsonPath('data.route_segments.0.encoded_shape', 'encoded-shape');

        $this->assertSame(1, Order::query()->where('user_id', $customer->id)->count());
        $this->assertSame(1, OrderRouteSegment::query()->where('segment_type', 'restaurant_to_client')->count());
    }
}
