<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLoginLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $signedUrl,
        public int $expiresInMinutes = 15
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Secure Magic Sign-In Link')
            ->greeting('Hello!')
            ->line('Click the button below to sign in to your digital product account.')
            ->action('Sign In to Account', $this->signedUrl)
            ->line("This secure sign-in link will expire in {$this->expiresInMinutes} minutes.")
            ->line('If you did not request this link, no further action is required.');
    }
}
