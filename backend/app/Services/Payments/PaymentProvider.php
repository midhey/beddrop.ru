<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentProvider
{
    public function createPayment(Order $order, string $idempotencyKey): ProviderPayment;

    public function fetchPayment(string $providerPaymentId): ProviderPayment;
}
