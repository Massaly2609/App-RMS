<?php

namespace App\Notifications;

use App\Models\Repayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class RepaymentCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Repayment $repayment
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type'          => 'repayment_completed',
            'repayment_id'  => $this->repayment->id,
            'target_amount' => $this->repayment->target_amount,
            'amount_paid'   => $this->repayment->amount_paid,
            'completed_at'  => $this->repayment->completed_at,
            'message'       => "Votre remboursement de {$this->repayment->target_amount} FCFA est terminé. Vous êtes à nouveau éligible.",
        ]);
    }
}
