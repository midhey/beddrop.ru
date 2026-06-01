<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderPaymentService
{
    public const EVENT_PAYMENT_PAID = 'PAYMENT_PAID';

    public function __construct(
        private readonly PaymentProvider $provider,
    ) {
    }

    public function initiate(Order $order): Payment
    {
        $this->ensurePayable($order);

        $payment = DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->ensurePayable($lockedOrder);

            $payment = Payment::query()->firstOrCreate(
                ['order_id' => $lockedOrder->id],
                [
                    'provider' => 'yookassa',
                    'amount_value' => $lockedOrder->total_price,
                    'currency' => config('services.yookassa.currency', 'RUB'),
                    'idempotency_key' => "order-{$lockedOrder->id}-yookassa-v1",
                ],
            );

            if ($lockedOrder->payment_status === PaymentStatus::FAILED->value) {
                $payment->forceFill([
                    'provider_payment_id' => null,
                    'provider_status' => null,
                    'confirmation_url' => null,
                    'amount_value' => $lockedOrder->total_price,
                    'currency' => config('services.yookassa.currency', 'RUB'),
                    'idempotency_key' => $this->nextIdempotencyKey($lockedOrder, $payment),
                    'raw_payload' => null,
                    'provider_created_at' => null,
                    'synced_at' => null,
                    'confirmed_at' => null,
                    'failed_at' => null,
                ])->save();

                $lockedOrder->payment_status = PaymentStatus::PENDING->value;
                $lockedOrder->save();
            }

            return $payment;
        });

        if ($payment->provider_payment_id && $payment->confirmation_url) {
            return $payment->fresh('order');
        }

        try {
            $providerPayment = $this->provider->createPayment($order->fresh(), $payment->idempotency_key);
        } catch (Throwable $exception) {
            Log::warning('YooKassa payment creation failed', [
                'order_id' => $order->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new HttpResponseException(response()->json([
                'message' => app()->hasDebugModeEnabled()
                    ? 'Не удалось создать платеж: ' . $exception->getMessage()
                    : 'Не удалось создать платеж. Попробуйте позже.',
            ], 502));
        }

        return DB::transaction(function () use ($payment, $providerPayment) {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->applyProviderPayment($lockedPayment, $providerPayment);

            return $lockedPayment->fresh('order');
        });
    }

    public function sync(Payment $payment): Payment
    {
        if (! $payment->provider_payment_id) {
            throw new HttpResponseException(response()->json([
                'message' => 'Платеж еще не создан у провайдера.',
            ], 422));
        }

        try {
            $providerPayment = $this->provider->fetchPayment($payment->provider_payment_id);
        } catch (Throwable $exception) {
            Log::warning('YooKassa payment status sync failed', [
                'payment_id' => $payment->id,
                'provider_payment_id' => $payment->provider_payment_id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new HttpResponseException(response()->json([
                'message' => app()->hasDebugModeEnabled()
                    ? 'Не удалось проверить статус платежа: ' . $exception->getMessage()
                    : 'Не удалось проверить статус платежа. Попробуйте позже.',
            ], 502));
        }

        return DB::transaction(function () use ($payment, $providerPayment) {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->applyProviderPayment($lockedPayment, $providerPayment);

            return $lockedPayment->fresh('order');
        });
    }

    public function syncByProviderPaymentId(string $providerPaymentId): ?Payment
    {
        $payment = Payment::query()
            ->where('provider_payment_id', $providerPaymentId)
            ->first();

        if (! $payment) {
            return null;
        }

        return $this->sync($payment);
    }

    private function ensurePayable(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::ONLINE->value) {
            throw new HttpResponseException(response()->json([
                'message' => 'Оплата доступна только для онлайн-заказов.',
            ], 422));
        }

        if ($order->status !== OrderStatus::CREATED->value) {
            throw new HttpResponseException(response()->json([
                'message' => 'Платеж можно создать только для нового заказа.',
            ], 422));
        }

        if (! in_array($order->payment_status, [
            PaymentStatus::PENDING->value,
            PaymentStatus::FAILED->value,
        ], true)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Заказ уже не ожидает оплату.',
            ], 422));
        }
    }

    private function nextIdempotencyKey(Order $order, Payment $payment): string
    {
        $currentKey = $payment->idempotency_key;

        if (preg_match('/-v(\d+)$/', $currentKey, $matches)) {
            return "order-{$order->id}-yookassa-v" . ((int) $matches[1] + 1);
        }

        return "order-{$order->id}-yookassa-v2";
    }

    private function applyProviderPayment(Payment $payment, ProviderPayment $providerPayment): void
    {
        $payment->provider_payment_id = $providerPayment->id;
        $payment->provider_status = $providerPayment->status;
        $payment->confirmation_url = $providerPayment->confirmationUrl ?? $payment->confirmation_url;
        $payment->amount_value = $providerPayment->amountValue;
        $payment->currency = $providerPayment->currency;
        $payment->raw_payload = $providerPayment->rawPayload;
        $payment->provider_created_at = $providerPayment->createdAt;
        $payment->synced_at = now();

        $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

        if (
            $providerPayment->status === 'succeeded' &&
            $providerPayment->paid &&
            $this->matchesOrderAmount($order, $providerPayment)
        ) {
            $wasPaid = $order->payment_status === PaymentStatus::PAID->value;
            $order->payment_status = PaymentStatus::PAID->value;
            $payment->confirmed_at ??= now();

            if (! $wasPaid) {
                $this->recordPaymentPaidEvent($order, $payment, $providerPayment);
            }
        } elseif ($providerPayment->status === 'succeeded' && $providerPayment->paid) {
            $order->payment_status = PaymentStatus::FAILED->value;
            $payment->failed_at ??= now();
        } elseif ($providerPayment->status === 'waiting_for_capture') {
            $order->payment_status = PaymentStatus::AUTHORIZED->value;
        } elseif ($providerPayment->status === 'canceled') {
            $order->payment_status = PaymentStatus::FAILED->value;
            $payment->failed_at ??= now();
        }

        $order->save();
        $payment->save();
    }

    private function matchesOrderAmount(Order $order, ProviderPayment $providerPayment): bool
    {
        $expectedAmount = number_format((float) $order->total_price, 2, '.', '');
        $expectedCurrency = (string) config('services.yookassa.currency', 'RUB');

        return $providerPayment->amountValue === $expectedAmount
            && $providerPayment->currency === $expectedCurrency;
    }

    private function recordPaymentPaidEvent(Order $order, Payment $payment, ProviderPayment $providerPayment): void
    {
        OrderEvent::query()->firstOrCreate(
            [
                'order_id' => $order->id,
                'event' => self::EVENT_PAYMENT_PAID,
            ],
            [
                'payload' => [
                    'payment_id' => $payment->id,
                    'provider' => $payment->provider,
                    'provider_payment_id' => $providerPayment->id,
                    'provider_status' => $providerPayment->status,
                ],
            ],
        );
    }
}
