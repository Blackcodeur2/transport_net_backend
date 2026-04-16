<?php

namespace App\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

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
        $url = env('FRONTEND_URL'). '/reset-password?token'. $this->token. '$email='. urlencode($notifiable->email);
        return (new MailMessage)
            ->subject('Reinitialisation de votre mot de passe')
            ->greeting('Bonjour' . $notifiable->name)
            ->line('Vous avez demandez la reinitialisation de votre mot de passe')
            ->action('Reinitialiser mon mot de passe',$url)
            ->line('Ignorez ce mail si vous n\'ete pas a l\'origine de cette demande');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
