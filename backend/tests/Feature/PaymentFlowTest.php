<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\OrderPaymentService;
use App\Services\Payments\PaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\Fakes\FakePaymentProvider;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    private FakePaymentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.yookassa.currency' => 'RUB']);
        $this->provider = new FakePaymentProvider();
        $this->app->instance(PaymentProvider::class, $this->provider);
    }

    public function test_unauthenticated_user_cannot_initiate_payment(): void
    {
        $order = $this->pendingOnlineOrder();

        $this
            ->postJson("/api/v1/orders/{$order->id}/payment")
            ->assertUnauthorized();

        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }

    public function test_cross_user_cannot_initiate_payment(): void
    {
        $otherUser = $this->createUser();
        $order = $this->pendingOnlineOrder();

        $this
            ->actingAs($otherUser, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment")
            ->assertNotFound();

        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }

    public function test_owner_can_initiate_payment_for_own_pending_online_order(): void
    {
        $order = $this->pendingOnlineOrder(['total_price' => 888.50]);

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment")
            ->assertOk()
            ->assertJsonPath('payment.provider', 'yookassa')
            ->assertJsonPath('payment.provider_payment_id', '2-test-created')
            ->assertJsonPath('payment.confirmation_url', 'https://yookassa.test/confirm/2-test-created')
            ->assertJsonPath('order.payment_status', PaymentStatus::PENDING->value);

        $this->assertSame($order->id, $this->provider->createdForOrder?->id);
        $this->assertSame('888.50', number_format((float) $this->provider->createdForOrder?->total_price, 2, '.', ''));
        $this->assertSame("order-{$order->id}-yookassa-v1", $this->provider->lastIdempotencyKey);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_payment_id' => '2-test-created',
            'provider_status' => 'pending',
            'amount_value' => '888.50',
            'currency' => 'RUB',
        ]);
    }

    public function test_backend_uses_order_total_price_not_client_amount(): void
    {
        $order = $this->pendingOnlineOrder(['total_price' => 1200.25]);

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment", [
                'amount' => '1.00',
                'payment_status' => PaymentStatus::PAID->value,
            ])
            ->assertOk();

        $this->assertSame('1200.25', number_format((float) $this->provider->createdForOrder?->total_price, 2, '.', ''));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_client_cannot_mark_payment_paid_directly_and_mock_pay_route_is_gone(): void
    {
        $order = $this->pendingOnlineOrder();

        $this
            ->getJson("/api/v1/orders/{$order->id}/mock-pay")
            ->assertNotFound();

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment/sync", [
                'payment_status' => PaymentStatus::PAID->value,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_soft_payment_status_check_returns_missing_when_payment_is_not_created(): void
    {
        $order = $this->pendingOnlineOrder();

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment/status")
            ->assertOk()
            ->assertJsonPath('result', 'missing')
            ->assertJsonPath('order.payment_status', PaymentStatus::PENDING->value);
    }

    public function test_yookassa_success_webhook_marks_order_paid(): void
    {
        $order = $this->pendingOnlineOrder();
        $payment = $this->createPaymentForOrder($order, '2-test-success');
        $this->provider->fetchResponses['2-test-success'] = $this->provider->payment(
            id: '2-test-success',
            status: 'succeeded',
            paid: true,
            amount: number_format((float) $order->total_price, 2, '.', ''),
        );

        $this
            ->postJson('/api/v1/payments/yookassa/webhook', [
                'event' => 'payment.succeeded',
                'object' => [
                    'id' => '2-test-success',
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::PAID->value,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'provider_status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'event' => OrderPaymentService::EVENT_PAYMENT_PAID,
        ]);
    }

    public function test_payment_sync_marks_order_paid(): void
    {
        $order = $this->pendingOnlineOrder();
        $this->createPaymentForOrder($order, '2-test-sync');
        $this->provider->fetchResponses['2-test-sync'] = $this->provider->payment(
            id: '2-test-sync',
            status: 'succeeded',
            paid: true,
            amount: number_format((float) $order->total_price, 2, '.', ''),
        );

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment/sync")
            ->assertOk()
            ->assertJsonPath('order.payment_status', PaymentStatus::PAID->value);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'event' => OrderPaymentService::EVENT_PAYMENT_PAID,
        ]);
    }

    public function test_repeated_success_sync_does_not_duplicate_payment_paid_event(): void
    {
        $order = $this->pendingOnlineOrder();
        $this->createPaymentForOrder($order, '2-test-sync-repeat');
        $this->provider->fetchResponses['2-test-sync-repeat'] = $this->provider->payment(
            id: '2-test-sync-repeat',
            status: 'succeeded',
            paid: true,
            amount: number_format((float) $order->total_price, 2, '.', ''),
        );

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment/sync")
            ->assertOk();
        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment/sync")
            ->assertOk();

        $this->assertSame(
            1,
            $order->events()
                ->where('event', OrderPaymentService::EVENT_PAYMENT_PAID)
                ->count(),
        );
    }

    public function test_canceled_payment_marks_order_failed(): void
    {
        $order = $this->pendingOnlineOrder();
        $this->createPaymentForOrder($order, '2-test-canceled');
        $this->provider->fetchResponses['2-test-canceled'] = $this->provider->payment(
            id: '2-test-canceled',
            status: 'canceled',
            paid: false,
        );

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment/sync")
            ->assertOk()
            ->assertJsonPath('order.payment_status', PaymentStatus::FAILED->value);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_status' => 'canceled',
        ]);
    }

    public function test_owner_can_retry_failed_created_online_order_payment(): void
    {
        $order = $this->pendingOnlineOrder([
            'payment_status' => PaymentStatus::FAILED->value,
        ]);
        $payment = $this->createPaymentForOrder($order, '2-test-failed');
        $payment->update([
            'provider_status' => 'canceled',
            'failed_at' => now(),
        ]);

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment")
            ->assertOk()
            ->assertJsonPath('payment.provider_payment_id', '2-test-created')
            ->assertJsonPath('payment.confirmation_url', 'https://yookassa.test/confirm/2-test-created')
            ->assertJsonPath('order.payment_status', PaymentStatus::PENDING->value);

        $this->assertSame("order-{$order->id}-yookassa-v2", $this->provider->lastIdempotencyKey);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
            'provider_payment_id' => '2-test-created',
            'provider_status' => 'pending',
            'idempotency_key' => "order-{$order->id}-yookassa-v2",
            'failed_at' => null,
        ]);
    }

    public function test_success_with_wrong_provider_amount_does_not_mark_order_paid(): void
    {
        $order = $this->pendingOnlineOrder(['total_price' => 990.00]);
        $this->createPaymentForOrder($order, '2-test-wrong-amount');
        $this->provider->fetchResponses['2-test-wrong-amount'] = $this->provider->payment(
            id: '2-test-wrong-amount',
            status: 'succeeded',
            paid: true,
            amount: '1.00',
        );

        $this
            ->actingAs($order->user, 'api')
            ->postJson("/api/v1/orders/{$order->id}/payment/sync")
            ->assertOk()
            ->assertJsonPath('order.payment_status', PaymentStatus::FAILED->value);
    }

    public function test_cash_and_card_order_creation_returns_validation_error(): void
    {
        foreach ([PaymentMethod::CASH->value, PaymentMethod::CARD->value] as $method) {
            $customer = $this->createUser();
            $restaurant = $this->createRestaurant();
            $cart = $this->createActiveCart($customer, $restaurant);
            $this->addCartItem($cart, $this->createProduct($restaurant));
            $address = $this->createAddress($customer);

            $this
                ->actingAs($customer, 'api')
                ->postJson('/api/v1/orders', [
                    'delivery_address_id' => $address->id,
                    'payment_method' => $method,
                ])
                ->assertStatus(422)
                ->assertJsonPath('message', 'Сейчас доступна только онлайн-оплата. Оплата наличными и картой курьеру временно отключены.');
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function pendingOnlineOrder(array $attributes = [])
    {
        $customer = $this->createUser();
        $restaurant = $this->createRestaurant();

        return $this->createAcceptedOrder($customer, $restaurant, null, array_merge([
            'status' => OrderStatus::CREATED->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'payment_method' => PaymentMethod::ONLINE->value,
        ], $attributes));
    }

    private function createPaymentForOrder($order, string $providerPaymentId): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'provider' => 'yookassa',
            'provider_payment_id' => $providerPaymentId,
            'provider_status' => 'pending',
            'confirmation_url' => 'https://yookassa.test/confirm/' . $providerPaymentId,
            'amount_value' => $order->total_price,
            'currency' => 'RUB',
            'idempotency_key' => "order-{$order->id}-yookassa-v1",
        ]);
    }
}
