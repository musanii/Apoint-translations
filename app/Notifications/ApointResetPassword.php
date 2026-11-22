<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class ApointResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = URL::route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset your password | Apoint Translations')
            ->view('emails.auth.reset-password', [
                'recipientName' => $notifiable->name ?: 'there',
                'resetUrl' => $resetUrl,
            ]);
    }
}
