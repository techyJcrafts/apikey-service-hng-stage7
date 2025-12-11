<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_wallet_id',
        'receiver_wallet_id',
        'amount',
        'reference',
        'status',
        'sender_transaction_id',
        'receiver_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function senderWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id');
    }

    public function receiverWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'receiver_wallet_id');
    }

    public function senderTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'sender_transaction_id');
    }

    public function receiverTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'receiver_transaction_id');
    }
}
