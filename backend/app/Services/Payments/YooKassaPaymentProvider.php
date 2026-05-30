<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YooKassaPaymentProvider implements PaymentProvider
{
    private const API_BASE = 'https://api.yookassa.ru/v3';

    public function createPayment(Order $order, string $idempotencyKey): ProviderPayment
    {
        $payload = [
            'amount' => [
                'value' => number_format((float) $order->total_price, 2, '.', ''),
                'currency' => config('services.yookassa.currency', 'RUB'),
            ],
            'capture' => (bool) config('services.yookassa.capture', true),
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $this->returnUrl($order),
            ],
            'description' => "Заказ №{$order->id}",
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ];

        $response = $this->client()
            ->withHeaders(['Idempotence-Key' => $idempotencyKey])
            ->post(self::API_BASE . '/payments', $payload);

        if ($response->failed()) {
            throw new RuntimeException('YooKassa payment creation failed: ' . $response->body());
        }

        return $this->mapPayment($response->json());
    }

    public function fetchPayment(string $providerPaymentId): ProviderPayment
    {
        $response = $this->client()->get(self::API_BASE . '/payments/' . $providerPaymentId);

        if ($response->failed()) {
            throw new RuntimeException('YooKassa payment fetch failed: ' . $response->body());
        }

        return $this->mapPayment($response->json());
    }

    private function client()
    {
        $shopId = config('services.yookassa.shop_id');
        $secretKey = config('services.yookassa.secret_key');

        if (! $shopId || ! $secretKey) {
            throw new RuntimeException('YooKassa credentials are not configured.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withBasicAuth((string) $shopId, (string) $secretKey)
            ->timeout(15);
    }

    private function returnUrl(Order $order): string
    {
        $configured = config('services.yookassa.return_url');

        if ($configured) {
            return str_replace('{order}', (string) $order->id, (string) $configured);
        }

        return rtrim((string) config('app.frontend_url'), '/') . '/orders/' . $order->id;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapPayment(array $data): ProviderPayment
    {
        return new ProviderPayment(
            id: (string) $data['id'],
            status: (string) $data['status'],
            confirmationUrl: $data['confirmation']['confirmation_url'] ?? null,
            amountValue: (string) ($data['amount']['value'] ?? '0.00'),
            currency: (string) ($data['amount']['currency'] ?? config('services.yookassa.currency', 'RUB')),
            paid: (bool) ($data['paid'] ?? false),
            createdAt: $data['created_at'] ?? null,
            rawPayload: $data,
        );
    }
}
