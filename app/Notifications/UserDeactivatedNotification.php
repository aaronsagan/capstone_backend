<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class UserDeactivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, ?string $reason = null)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('User Account Deactivation - ' . $this->user->name)
            ->greeting('Hello Admin,')
            ->line('A user has deactivated their account.')
            ->line('**User Details:**')
            ->line('Name: ' . $this->user->name)
            ->line('Email: ' . $this->user->email)
            ->line('Role: ' . ucfirst($this->user->role));

        if ($this->reason) {
            $mail->line('**Reason:** ' . $this->reason);
        }

        $mail->line('The user will need admin approval to reactivate their account when they try to log in again.')
            ->action('View User Management', url('/admin/users'));

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_deactivated',
            'title' => 'User Account Deactivated',
            'message' => $this->user->name . ' has deactivated their account.',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'reason' => $this->reason,
        ];
    }
}
