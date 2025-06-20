<?php

namespace Rcv\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ModuleUpdateAvailable extends Notification
{
    use Queueable;

    protected $updates;

    public function __construct(array $updates)
    {
        $this->updates = $updates;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject('Module Updates Available')
            ->line('The following modules have updates available:');

        foreach ($this->updates as $update) {
            $message->line("- {$update['name']} (Current: {$update['current_version']}, Available: {$update['version']})");
        }

        $message->action('View Updates', url('/admin/modules/marketplace'))
            ->line('Please review and apply these updates at your earliest convenience.');

        return $message;
    }

    public function toArray($notifiable)
    {
        return [
            'updates' => $this->updates,
            'timestamp' => now(),
        ];
    }
} 