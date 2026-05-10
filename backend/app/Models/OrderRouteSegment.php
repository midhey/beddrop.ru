<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRouteSegment extends Model
{
    protected $fillable = [
        'order_id',
        'segment_type',
        'mode',
        'distance_meters',
        'duration_seconds',
        'encoded_shape',
        'raw_response_json',
        'settings_snapshot_json',
    ];

    protected $casts = [
        'distance_meters' => 'integer',
        'duration_seconds' => 'integer',
        'raw_response_json' => 'array',
        'settings_snapshot_json' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
