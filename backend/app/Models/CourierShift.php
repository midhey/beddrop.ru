<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function courier()
    {
        return $this->belongsTo(CourierProfile::class, 'courier_user_id', 'user_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }
}
