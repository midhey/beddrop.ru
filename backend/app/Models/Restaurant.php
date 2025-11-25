<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'address_id',
        'phone',
        'is_active',
        'prep_time_min',
        'prep_time_max',
        'logo_media_id',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'prep_time_min' => 'integer',
        'prep_time_max' => 'integer',
    ];

    protected $with = ['logo'];

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function logo()
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
