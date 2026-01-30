<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimelinePost extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'content',
        'media_url',
        'metadata',
        'visibility',
        'country',
        'city',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(TimelineReaction::class, 'post_id');
    }

    // public function comments(): HasMany
    // {
    //     return $this->reactions()->where('type', 'comment');
    // }

    public function likes(): HasMany
    {
        return $this->reactions()->where('type', 'like');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'timeline_post_id');
    }

}
