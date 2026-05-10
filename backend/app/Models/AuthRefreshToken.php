<?php

namespace App\Models;

use App\Enums\AuthClientType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthRefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'family_id',
        'rotated_from_id',
        'client_type',
        'token_hash',
        'device_name',
        'user_agent',
        'ip_address',
        'last_used_at',
        'expires_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected $casts = [
        'client_type' => AuthClientType::class,
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rotatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rotated_from_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
