<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (settings('mail_notifications_enabled', false) && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    abstract public function title(): string;

    abstract public function message(): string;

    abstract public function url(): string;

    public function icon(): string
    {
        return 'bi-bell';
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
            'icon' => $this->icon(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message())
            ->action('Open InternTrack', $this->url())
            ->line('This is an automated message from '.settings('university_name', 'InternTrack').'.');
    }
}
