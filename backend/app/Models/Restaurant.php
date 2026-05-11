<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'description',
        'slug',
        'address_id',
        'phone',
        'is_active',
        'accepts_orders',
        'timezone',
        'opens_at',
        'closes_at',
        'closed_reason',
        'prep_time_min',
        'prep_time_max',
        'logo_media_id',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'accepts_orders' => 'boolean',
        'prep_time_min' => 'integer',
        'prep_time_max' => 'integer',
    ];

    protected $with = ['logo'];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function prepTimeAverageMinutes(): ?int
    {
        if ($this->prep_time_min !== null && $this->prep_time_max !== null) {
            return (int) ceil(($this->prep_time_min + $this->prep_time_max) / 2);
        }

        return $this->prep_time_min;
    }

    /**
     * @return array{
     *     is_open: bool,
     *     accepts_orders: bool,
     *     timezone: string,
     *     opens_at: string|null,
     *     closes_at: string|null,
     *     closed_reason: string|null,
     *     status: string
     * }
     */
    public function availability(?CarbonInterface $now = null): array
    {
        $timezone = $this->timezone ?: 'Europe/Moscow';
        $acceptsOrders = (bool) ($this->accepts_orders ?? true);
        $opensAt = $this->normalizeClockTime($this->opens_at);
        $closesAt = $this->normalizeClockTime($this->closes_at);

        $status = 'open';

        if (!$this->is_active) {
            $status = 'inactive';
        } elseif (!$acceptsOrders) {
            $status = 'manually_closed';
        } elseif (!$this->isWithinWorkingHours($now, $timezone, $opensAt, $closesAt)) {
            $status = 'closed_by_schedule';
        }

        return [
            'is_open' => $status === 'open',
            'accepts_orders' => $acceptsOrders,
            'timezone' => $timezone,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'closed_reason' => $this->closed_reason,
            'status' => $status,
        ];
    }

    public function isOpenForOrders(?CarbonInterface $now = null): bool
    {
        return $this->availability($now)['is_open'];
    }

    private function normalizeClockTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    private function isWithinWorkingHours(
        ?CarbonInterface $now,
        string $timezone,
        ?string $opensAt,
        ?string $closesAt
    ): bool {
        if ($opensAt === null || $closesAt === null) {
            return true;
        }

        $current = $now?->copy()->timezone($timezone) ?? now($timezone);
        $currentMinutes = ((int) $current->format('H')) * 60 + (int) $current->format('i');
        $opensMinutes = $this->clockTimeToMinutes($opensAt);
        $closesMinutes = $this->clockTimeToMinutes($closesAt);

        if ($opensMinutes === $closesMinutes) {
            return true;
        }

        if ($closesMinutes > $opensMinutes) {
            return $currentMinutes >= $opensMinutes && $currentMinutes < $closesMinutes;
        }

        return $currentMinutes >= $opensMinutes || $currentMinutes < $closesMinutes;
    }

    private function clockTimeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
