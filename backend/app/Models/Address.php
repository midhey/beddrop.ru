<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'line1',
        'line2',
        'city',
        'postal_code',
        'lat',
        'lng',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class, 'address_id');
    }
}
