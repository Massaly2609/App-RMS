<?php

namespace App\Notifications;

use App\Models\Rotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class RotationExecutedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Rotation $rotation
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
        // plus tard: ['database', 'broadcast']
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type'      => 'rotation_executed',
            'rotation_id' => $this->rotation->id,
            'amount'    => $this->rotation->amount,
            'source'    => $this->rotation->source,
            'triggered_at' => $this->rotation->triggered_at,
            'message'   => "Vous avez reçu un gain de {$this->rotation->amount} FCFA.",
        ]);
    }
}
