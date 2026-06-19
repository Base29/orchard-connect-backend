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

    protected function getFilamentStyles(): array
    {
        $type = $this->metadata['type'] ?? '';
        
        switch ($type) {
            case 'post_mention':
            case 'comment_mention':
                return [
                    'icon' => 'heroicon-o-at-symbol',
                    'iconColor' => 'success',
                    'status' => 'success',
                ];
            case 'post_mention_all':
            case 'comment_mention_all':
            case 'new_announcement':
                return [
                    'icon' => 'heroicon-o-megaphone',
                    'iconColor' => 'warning',
                    'status' => 'warning',
                ];
            case 'comment':
            case 'comment_reply':
            case 'listing_comment':
                return [
                    'icon' => 'heroicon-o-chat-bubble-left-right',
                    'iconColor' => 'info',
                    'status' => 'info',
                ];
            case 'like':
                return [
                    'icon' => 'heroicon-o-heart',
                    'iconColor' => 'danger',
                    'status' => 'danger',
                ];
            case 'verification_approved':
                return [
                    'icon' => 'heroicon-o-check-circle',
                    'iconColor' => 'success',
                    'status' => 'success',
                ];
            case 'verification_rejected':
                return [
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'iconColor' => 'warning',
                    'status' => 'warning',
                ];
            case 'listing_status':
            case 'moderation_listing_submitted':
                return [
                    'icon' => 'heroicon-o-shopping-bag',
                    'iconColor' => 'primary',
                    'status' => 'success',
                ];
            case 'ticket_status':
                return [
                    'icon' => 'heroicon-o-ticket',
                    'iconColor' => 'success',
                    'status' => 'success',
                ];
            case 'moderation_verification':
                return [
                    'icon' => 'heroicon-o-shield-check',
                    'iconColor' => 'warning',
                    'status' => 'warning',
                ];
            case 'moderation_post_flagged':
            case 'moderation_listing_flagged':
                return [
                    'icon' => 'heroicon-o-flag',
                    'iconColor' => 'danger',
                    'status' => 'danger',
                ];
            case 'new_news':
                return [
                    'icon' => 'heroicon-o-newspaper',
                    'iconColor' => 'info',
                    'status' => 'info',
                ];
            case 'new_poll':
                return [
                    'icon' => 'heroicon-o-chart-bar',
                    'iconColor' => 'primary',
                    'status' => 'success',
                ];
            default:
                return [
                    'icon' => 'heroicon-o-bell',
                    'iconColor' => 'gray',
                    'status' => 'info',
                ];
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $styles = $this->getFilamentStyles();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->message,
            'message' => $this->message,
            'target_url' => $this->targetUrl,
            'icon' => $styles['icon'],
            'iconColor' => $styles['iconColor'],
            'status' => $styles['status'],
            'color' => null,
            'duration' => 'persistent',
            'format' => 'filament',
            'view' => null,
            'viewData' => [],
            'actions' => [
                [
                    'name' => 'view',
                    'alpineClickHandler' => null,
                    'color' => null,
                    'event' => null,
                    'eventData' => [],
                    'dispatchDirection' => false,
                    'dispatchToComponent' => null,
                    'extraAttributes' => [],
                    'icon' => null,
                    'iconPosition' => 'before',
                    'iconSize' => null,
                    'isOutlined' => false,
                    'isDisabled' => false,
                    'label' => 'View Details',
                    'shouldClose' => true,
                    'shouldMarkAsRead' => false,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'shouldPostToUrl' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => $this->targetUrl,
                    'view' => 'filament::components.link',
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
        $styles = $this->getFilamentStyles();

        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->message,
            'message' => $this->message,
            'target_url' => $this->targetUrl,
            'icon' => $styles['icon'],
            'iconColor' => $styles['iconColor'],
            'status' => $styles['status'],
            'color' => null,
            'duration' => 'persistent',
            'format' => 'filament',
            'view' => null,
            'viewData' => [],
            'actions' => [
                [
                    'name' => 'view',
                    'alpineClickHandler' => null,
                    'color' => null,
                    'event' => null,
                    'eventData' => [],
                    'dispatchDirection' => false,
                    'dispatchToComponent' => null,
                    'extraAttributes' => [],
                    'icon' => null,
                    'iconPosition' => 'before',
                    'iconSize' => null,
                    'isOutlined' => false,
                    'isDisabled' => false,
                    'label' => 'View Details',
                    'shouldClose' => true,
                    'shouldMarkAsRead' => false,
                    'shouldMarkAsUnread' => false,
                    'shouldOpenUrlInNewTab' => false,
                    'shouldPostToUrl' => false,
                    'size' => 'sm',
                    'tooltip' => null,
                    'url' => $this->targetUrl,
                    'view' => 'filament::components.link',
                ]
            ],
            'metadata' => $this->metadata,
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
