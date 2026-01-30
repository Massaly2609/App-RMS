<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\TimelinePost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PostCommented extends Notification
{
    use Queueable;

    public function __construct(
        public TimelinePost $post,
        public Comment $comment
    )
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'post_id' => $this->post->id,
            'comment_id' => $this->comment->id,
            'commenter_name' => $this->comment->user->first_name . ' ' . $this->comment->user->last_name,
            'comment_preview' => substr($this->comment->content, 0, 50),
            'message' => $this->comment->user->first_name . ' ' . $this->comment->user->last_name . ' a commenté ton post : "' . substr($this->comment->content, 0, 30) . '"',
        ]);
    }
}
