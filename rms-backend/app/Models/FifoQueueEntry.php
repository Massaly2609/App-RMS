<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FifoQueueEntry extends Model
{
     protected $fillable = [
        'user_id',
        'entered_at',
        'active',
        'position_snapshot',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'active' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
