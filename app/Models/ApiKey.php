<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'permissions',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usages()
    {
        return $this->hasMany(ApiKeyUsage::class);
    }

    /**
     * Check if API key is valid (not revoked and not expired)
     */
    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    /**
     * Check if API key has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Mark API key as used (updates last_used_at)
     */
    public function markUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke API key
     */
    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope for active keys
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }
}
