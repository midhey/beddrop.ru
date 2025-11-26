<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierProfile extends Model
{
    protected $table = 'courier_profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'status',
        'vehicle',
        'rating',
    ];

    protected $casts = [
        'rating' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shifts()
    {
        return $this->hasMany(CourierShift::class, 'courier_user_id', 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'courier_id', 'user_id');
    }
}
