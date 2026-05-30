<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_payment_id',
        'provider_status',
        'confirmation_url',
        'amount_value',
        'currency',
        'idempotency_key',
        'raw_payload',
        'provider_created_at',
        'synced_at',
        'confirmed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount_value' => 'decimal:2',
        'raw_payload' => 'array',
        'provider_created_at' => 'datetime',
        'synced_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
