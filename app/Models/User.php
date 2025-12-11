<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Wallet;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get active API keys (not revoked, not expired)
     */
    public function activeApiKeys()
    {
        return $this->apiKeys()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }
}
