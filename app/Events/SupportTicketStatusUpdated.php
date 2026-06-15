<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $ticketId;
    public string $status;
    public string $trackingId;
    public string $subject;
    public string $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $ticketId, string $status, string $trackingId, string $subject, string $userId)
    {
        $this->ticketId = $ticketId;
        $this->status = $status;
        $this->trackingId = $trackingId;
        $this->subject = $subject;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'SupportTicketStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'status' => $this->status,
            'tracking_id' => $this->trackingId,
            'subject' => $this->subject,
        ];
    }
}
