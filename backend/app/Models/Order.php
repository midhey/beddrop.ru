<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'restaurant_id',
        'courier_id',
        'status',
        'payment_status',
        'payment_method',
        'total_price',
        'comment',
        'delivery_address_id',
        'delivery_lat',
        'delivery_lng',
    ];

    protected $casts = [
        'total_price'      => 'decimal:2',
        'delivery_lat'     => 'float',
        'delivery_lng'     => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function courier()
    {
        return $this->belongsTo(CourierProfile::class, 'courier_id', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class);
    }
}
