<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeEmployeeNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $token;
    protected $businessName;

    public function __construct($user, $token = null, $businessName = null)
    {
        $this->user = $user;
        $this->token = $token;
        $this->businessName = $businessName;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)->greeting('Hello ' . $this->user->name . ',');

        if ($this->token) {
            $resetUrl = url(route('password.reset', [
                'token' => $this->token,
                'email' => $this->user->email,
            ], false));

            $mail->subject('Welcome to ' . config('app.name') . ' - Set Up Your Account')
                ->line('Welcome to ' . config('app.name') . '! Your account has been created.')
                ->line('To get started, please set your password by clicking the button below:')
                ->action('Set Password', $resetUrl)
                ->line('This link will expire in ' . config('auth.passwords.users.expire') . ' minutes.');
        } else {
            $where = $this->businessName ? " at {$this->businessName}" : '';

            $mail->subject('You have been added as an employee' . $where)
                ->line("You've been added as an employee{$where} on " . config('app.name') . '.')
                ->line('Log in with your existing account to get started - no new password needed.')
                ->action('Log In', route('login'));
        }

        return $mail
            ->line('If you did not expect this email, please contact our support team.')
            ->salutation('Best regards, ' . config('app.name'));
    }
}