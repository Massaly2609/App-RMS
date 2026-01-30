<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EligibleNextGain extends Model
{
    protected $fillable = [
        'user_id',
        'became_eligible_at',
        'processed',
    ];

    protected $casts = [
        'became_eligible_at' => 'datetime',
        'processed' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
