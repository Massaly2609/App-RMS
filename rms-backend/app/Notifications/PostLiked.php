<?php

namespace App\Notifications;

use App\Models\TimelinePost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PostLiked extends Notification
{
    use Queueable;

    public function __construct(public TimelinePost $post)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database']; // stocke en base de données
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'post_id' => $this->post->id,
            'post_content' => substr($this->post->content ?? '', 0, 50),
            'liker_name' => $this->post->user->first_name . ' ' . $this->post->user->last_name,
            'message' => $this->post->user->first_name . ' ' . $this->post->user->last_name . ' a aimé ton post "' . substr($this->post->content ?? '', 0, 30) . '"',
        ]);
    }
}
