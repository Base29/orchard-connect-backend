<?php

namespace Tests\Feature;

use App\Events\CommentCreated;
use App\Events\PostCreated;
use App\Models\Post;
use App\Models\ResidentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CommunityFeedRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private User $verifiedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create verified resident
        $this->verifiedUser = User::factory()->create(['name' => 'Faisal Hussain']);
        ResidentProfile::create([
            'user_id' => $this->verifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);
    }

    /**
     * Test creating a post broadcasts the PostCreated event.
     */
    public function test_creating_post_broadcasts_event(): void
    {
        Event::fake([PostCreated::class]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Hello community! This is a real-time post.',
            ]);

        $response->assertStatus(201);

        Event::assertDispatched(PostCreated::class, function ($event) {
            return $event->post->content === 'Hello community! This is a real-time post.' &&
                   $event->post->user_id === $this->verifiedUser->id;
        });
    }

    /**
     * Test creating a comment broadcasts the CommentCreated event.
     */
    public function test_creating_comment_broadcasts_event(): void
    {
        Event::fake([CommentCreated::class]);

        // First create a post
        $post = Post::create([
            'user_id' => $this->verifiedUser->id,
            'content' => 'Base post content',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'This is a real-time comment.',
            ]);

        $response->assertStatus(201);

        Event::assertDispatched(CommentCreated::class, function ($event) use ($post) {
            return $event->comment->content === 'This is a real-time comment.' &&
                   $event->comment->post_id === $post->id &&
                   $event->comment->user_id === $this->verifiedUser->id;
        });
    }
}
