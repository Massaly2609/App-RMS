<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class TimelineReaction extends Model
{
     protected $fillable = [
        'post_id',
        'user_id',
        'type',
        'comment_text',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(TimelinePost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
