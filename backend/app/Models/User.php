<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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

    public function isAdmin(): bool {
        return $this->is_admin;
    }

    public function isBanned(): bool {
        return $this->is_banned;
    }

		//JWT
		public function getJWTIdentifier() {
			return $this->getKey();
		}

		public function getJWTCustomClaims() {
			return [];
		}
}
