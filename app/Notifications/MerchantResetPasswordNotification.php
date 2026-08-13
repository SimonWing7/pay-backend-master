<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token, public string $email) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('merchant.password.reset', [
            'token' => $this->token,
            'email' => $this->email,
        ]);

        return (new MailMessage)
            ->subject('Reset Your Edfundo Pay Password')
            ->greeting('Hello,')
            ->line('You are receiving this email because a password reset was requested for your Edfundo Pay merchant account.')
            ->action('Reset Password', $url)
            ->line('This link expires in 60 minutes.')
            ->line('If you did not request a password reset, no action is needed.');
    }
}
