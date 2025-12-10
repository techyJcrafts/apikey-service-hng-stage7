<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_number',
        'balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get balance in kobo (for Paystack)
     */
    public function getBalanceInKobo(): int
    {
        return (int) ($this->balance * 100);
    }

    /**
     * Format wallet number for display
     */
    public function getFormattedWalletNumberAttribute(): string
    {
        $number = $this->wallet_number;
        return substr($number, 0, 4) . ' ' . substr($number, 4, 4) . ' ' . substr($number, 8);
    }
}
