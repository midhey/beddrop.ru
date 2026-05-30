<?php

namespace App\Services\Payments;

final readonly class ProviderPayment
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public string $id,
        public string $status,
        public ?string $confirmationUrl,
        public string $amountValue,
        public string $currency,
        public bool $paid,
        public ?string $createdAt,
        public array $rawPayload,
    ) {
    }
}
