<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierLocation extends Model
{
    protected $fillable = [
        'courier_user_id',
        'lat',
        'lng',
        'accuracy',
        'heading',
        'speed',
        'recorded_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'accuracy' => 'float',
        'heading' => 'float',
        'speed' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class, 'courier_user_id', 'user_id');
    }
}
