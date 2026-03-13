<?php

namespace App\Models;

use App\Enums\RestaurantStaffRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

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

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function courierProfile(): HasOne
    {
        return $this->hasOne(CourierProfile::class, 'user_id');
    }

    public function courierShifts(): HasMany
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
        $roleValues = array_map(
            static fn (RestaurantStaffRole|string $role): string => $role instanceof RestaurantStaffRole ? $role->value : $role,
            $roles
        );

        if(!$this->relationLoaded('restaurants')) {
            $this->load('restaurants');
        }

        $restaurantMembership = $this->restaurants->firstWhere('id', $restaurant->id);

        if(!$restaurantMembership) {
            return false;
        }

        return in_array($restaurantMembership->pivot->role, $roleValues, true);
    }

    public function isStaffOf(Restaurant $restaurant): bool
    {
        return $this->hasRestaurantRole($restaurant, [
            RestaurantStaffRole::OWNER,
            RestaurantStaffRole::MANAGER,
            RestaurantStaffRole::STAFF,
        ]);
    }

    public function isManagerOrOwnerOf(Restaurant $restaurant): bool
    {
        return $this->hasRestaurantRole($restaurant, [
            RestaurantStaffRole::OWNER,
            RestaurantStaffRole::MANAGER,
        ]);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
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

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
