<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JackpotWallet extends Model
{
     protected $fillable = [
        'balance',
        'currency',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(JackpotTransaction::class);
    }
}
