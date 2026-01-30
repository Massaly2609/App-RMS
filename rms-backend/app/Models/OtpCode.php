<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OtpCode extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'attempts',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'bool',
    ];

    // Scope pratique pour récupérer uniquement les OTP encore valides
    public function scopeValid(Builder $query, string $phone): Builder
    {
        return $query->where('phone', $phone)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now());
    }
}
