<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JackpotTransaction extends Model
{
    protected $fillable = [
        'jackpot_wallet_id',
        'type',
        'direction',
        'amount',
        'related_transaction_id',
        'related_rotation_id',
    ];

    public function jackpotWallet(): BelongsTo
    {
        return $this->belongsTo(JackpotWallet::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }

    public function rotation(): BelongsTo
    {
        return $this->belongsTo(Rotation::class, 'related_rotation_id');
    }
}
