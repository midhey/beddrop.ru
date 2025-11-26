<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'email',
        'phone',
        'password',
        'name',
        'is_admin',
        'is_banned'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_banned' => 'boolean',
    ];

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function courierProfile()
    {
        return $this->hasOne(CourierProfile::class, 'user_id');
    }

    public function courierShifts()
    {
        return $this->hasMany(CourierShift::class, 'courier_user_id', 'id');
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class)
            ->withPivot('role');
    }

    public function hasRestaurantRole(Restaurant $restaurant, array $roles): bool
    {
        if(!$this->relationLoaded('restaurants')) {
            $this->load('restaurants');
        }

        $rel = $this->restaurants->firstWhere('id', $restaurant->id);

        if(!$rel) {
            return false;
        }

        return in_array($rel->pivot->role, $roles, true);
    }

    public function isStaffOf(Restaurant $restaurant): bool
    {
        return $this->hasRestaurantRole($restaurant, ['OWNER', 'MANAGER', 'STAFF']);
    }

    public function isManagerOrOwnerOf(Restaurant $restaurant): bool
    {
        return $this->hasRestaurantRole($restaurant, ['OWNER', 'MANAGER']);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isBanned(): bool
    {
        return $this->is_banned;
    }

    //JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
