<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'courier_fee',
        'comment',
        'delivery_address_id',
        'delivery_lat',
        'delivery_lng',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'courier_fee' => 'decimal:2',
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class, 'courier_id', 'user_id');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }
}
