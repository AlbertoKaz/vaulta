<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WorkspaceInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Invitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'invitations.accept',
            $this->invitation->expires_at ?? now()->addDays(7),
            [
                'token' => $this->invitation->token,
            ]
        );

        return (new MailMessage)
            ->subject('You have been invited to Vaulta')
            ->greeting('Hello!')
            ->line("You have been invited to join {$this->invitation->workspace->name}.")
            ->line("Role: {$this->invitation->role->value}")
            ->action('Accept invitation', $url)
            ->line('This invitation will expire soon.')
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
