<?php

namespace App\Models;

use App\Enums\CourierShiftStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierShift extends Model
{
    protected $table = 'courier_shifts';

    protected $fillable = [
        'courier_user_id',
        'started_at',
        'ended_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class, 'courier_user_id', 'user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', CourierShiftStatus::OPEN->value);
    }
}
