<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResidentVerificationStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $status;
    public ?string $rejectionReason;
    public ?string $rejectionMessage;
    public string $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $status, ?string $rejectionReason, ?string $rejectionMessage, string $userId)
    {
        $this->status = $status;
        $this->rejectionReason = $rejectionReason;
        $this->rejectionMessage = $rejectionMessage;
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
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'rejection_reason' => $this->rejectionReason,
            'rejection_message' => $this->rejectionMessage,
        ];
    }
}
