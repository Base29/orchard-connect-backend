<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Post $post;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('feed'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'PostCreated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // Ensure necessary relationships and counts are loaded
        $this->post->load(['user.residentProfile', 'user.roles', 'likes']);
        $this->post->loadCount(['comments', 'likes']);

        return [
            'post' => $this->post,
        ];
    }
}
