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

    /**
     * $token is only present for a genuinely new account (set-password
     * link). An existing account being added as an employee at another
     * business (same login, no new password needed) passes $token = null,
     * which sends a plain welcome/notice email with no reset link at all.
     */
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
