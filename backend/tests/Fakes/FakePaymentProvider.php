<?php

namespace Tests\Fakes;

use App\Models\Order;
use App\Services\Payments\PaymentProvider;
use App\Services\Payments\ProviderPayment;

class FakePaymentProvider implements PaymentProvider
{
    public ?Order $createdForOrder = null;
    public ?string $lastIdempotencyKey = null;
    public ProviderPayment $createResponse;
    /** @var array<string, ProviderPayment> */
    public array $fetchResponses = [];

    public function __construct()
    {
        $this->createResponse = $this->payment(
            id: '2-test-created',
            status: 'pending',
            confirmationUrl: 'https://yookassa.test/confirm/2-test-created',
            paid: false,
        );
    }

    public function createPayment(Order $order, string $idempotencyKey): ProviderPayment
    {
        $this->createdForOrder = $order->replicate();
        $this->createdForOrder->id = $order->id;
        $this->lastIdempotencyKey = $idempotencyKey;

        return $this->payment(
            id: $this->createResponse->id,
            status: $this->createResponse->status,
            confirmationUrl: $this->createResponse->confirmationUrl,
            paid: $this->createResponse->paid,
            amount: number_format((float) $order->total_price, 2, '.', ''),
        );
    }

    public function fetchPayment(string $providerPaymentId): ProviderPayment
    {
        return $this->fetchResponses[$providerPaymentId] ?? $this->createResponse;
    }

    public function payment(
        string $id,
        string $status,
        ?string $confirmationUrl = null,
        bool $paid = false,
        string $amount = '777.00',
    ): ProviderPayment {
        return new ProviderPayment(
            id: $id,
            status: $status,
            confirmationUrl: $confirmationUrl,
            amountValue: $amount,
            currency: 'RUB',
            paid: $paid,
            createdAt: '2026-05-31T10:00:00+00:00',
            rawPayload: [
                'id' => $id,
                'status' => $status,
                'paid' => $paid,
                'amount' => [
                    'value' => $amount,
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'confirmation_url' => $confirmationUrl,
                ],
            ],
        );
    }
}
