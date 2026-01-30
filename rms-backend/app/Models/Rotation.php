<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Rotation extends Model
{
     protected $fillable = [
        'user_id',
        'amount',
        'source',
        'queue_entry_id',
        'eligible_next_gain_id',
        'triggered_at',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function queueEntry(): BelongsTo
    {
        return $this->belongsTo(FifoQueueEntry::class, 'queue_entry_id');
    }

    public function eligibleNextGain(): BelongsTo
    {
        return $this->belongsTo(EligibleNextGain::class, 'eligible_next_gain_id');
    }
}
