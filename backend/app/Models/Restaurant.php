<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
