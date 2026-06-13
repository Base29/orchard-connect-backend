<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class GeneralNotification extends Notification
{
    use Queueable;

    public string $title;
    public string $message;
    public string $targetUrl;
    public array $metadata;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, string $targetUrl, array $metadata = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->targetUrl = $targetUrl;
        $this->metadata = $metadata;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'message' => $this->message,
            'target_url' => $this->targetUrl,
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'View Details',
                    'url' => $this->targetUrl,
                    'shouldClose' => true,
                    'shouldOpenUrlInNewTab' => false,
                    'view' => 'filament-actions::button-action',
                ]
            ],
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->message,
            'message' => $this->message,
            'target_url' => $this->targetUrl,
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'View Details',
                    'url' => $this->targetUrl,
                    'shouldClose' => true,
                    'shouldOpenUrlInNewTab' => false,
                    'view' => 'filament-actions::button-action',
                ]
            ],
            'metadata' => $this->metadata,
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
