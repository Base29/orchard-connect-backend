<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\ResidentProfile;
use App\Notifications\GeneralNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MentionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $verifiedUser;
    private User $otherVerifiedUser;
    private User $unverifiedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create current user (verified)
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

        // Create another verified resident
        $this->otherVerifiedUser = User::factory()->create(['name' => 'John Doe']);
        ResidentProfile::create([
            'user_id' => $this->otherVerifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block B',
            'house_number' => '200',
            'user_type' => 'tenant',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        // Create an unverified user
        $this->unverifiedUser = User::factory()->create(['name' => 'Jane Smith']);
        ResidentProfile::create([
            'user_id' => $this->unverifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block C',
            'house_number' => '300',
            'user_type' => 'owner',
            'is_verified' => false,
            'status' => 'pending',
        ]);
    }

    /**
     * Test unverified resident is blocked from searching mentions.
     */
    public function test_unverified_resident_cannot_search_mentions(): void
    {
        $response = $this->actingAs($this->unverifiedUser, 'sanctum')
            ->getJson('/api/residents/search-mentions?query=John');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Action locked. Residency verification required.');
    }

    /**
     * Test verified resident can search mentions.
     */
    public function test_verified_resident_can_search_mentions(): void
    {
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson('/api/residents/search-mentions?query=John');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $this->otherVerifiedUser->id,
                'name' => 'John Doe',
            ]);
    }

    /**
     * Test verified resident can mention another verified resident when creating a post.
     */
    public function test_mention_in_post_sends_notification(): void
    {
        Notification::fake();

        $content = "Hello @[John Doe](user:{$this->otherVerifiedUser->id}) welcome to the community!";

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson('/api/posts', [
                'content' => $content,
            ]);

        $response->assertStatus(201);

        Notification::assertSentTo(
            $this->otherVerifiedUser,
            GeneralNotification::class,
            function ($notification, $channels) {
                $data = $notification->toArray($this->otherVerifiedUser);
                return $data['title'] === 'New Mention in Post' &&
                    str_contains($data['message'], 'Faisal Hussain mentioned you');
            }
        );
    }

    /**
     * Test verified resident can mention another verified resident when creating a comment.
     */
    public function test_mention_in_comment_sends_notification(): void
    {
        Notification::fake();

        // Create a post
        $post = Post::create([
            'user_id' => $this->verifiedUser->id,
            'content' => 'Standard feed post',
            'status' => 'published',
        ]);

        $content = "Check this out @[John Doe](user:{$this->otherVerifiedUser->id})";

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => $content,
            ]);

        $response->assertStatus(201);

        Notification::assertSentTo(
            $this->otherVerifiedUser,
            GeneralNotification::class,
            function ($notification, $channels) {
                $data = $notification->toArray($this->otherVerifiedUser);
                return $data['title'] === 'New Mention in Comment' &&
                    str_contains($data['message'], 'Faisal Hussain mentioned you');
            }
        );
    }

    /**
     * Test verified resident can mention @all in a post and notify all verified residents.
     */
    public function test_mention_all_in_post_notifies_all_verified_residents(): void
    {
        Notification::fake();

        $content = "Hello @[all](user:all) this is a community alert!";

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson('/api/posts', [
                'content' => $content,
            ]);

        $response->assertStatus(201);

        // Faisal Hussain (verifiedUser) posted, so otherVerifiedUser should be notified,
        // but unverifiedUser should NOT be notified because she is not verified.
        Notification::assertSentTo(
            $this->otherVerifiedUser,
            GeneralNotification::class,
            function ($notification, $channels) {
                $data = $notification->toArray($this->otherVerifiedUser);
                return $data['title'] === 'Community Alert' &&
                    str_contains($data['message'], 'Faisal Hussain mentioned everyone');
            }
        );

        Notification::assertNotSentTo(
            $this->unverifiedUser,
            GeneralNotification::class
        );

        Notification::assertNotSentTo(
            $this->verifiedUser,
            GeneralNotification::class
        );
    }

    /**
     * Test verified resident can mention @all in a comment and notify all verified residents.
     */
    public function test_mention_all_in_comment_notifies_all_verified_residents(): void
    {
        Notification::fake();

        // Create a post
        $post = Post::create([
            'user_id' => $this->verifiedUser->id,
            'content' => 'Standard feed post',
            'status' => 'published',
        ]);

        $content = "Important comment for @[all](user:all)!";

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => $content,
            ]);

        $response->assertStatus(201);

        Notification::assertSentTo(
            $this->otherVerifiedUser,
            GeneralNotification::class,
            function ($notification, $channels) {
                $data = $notification->toArray($this->otherVerifiedUser);
                return $data['title'] === 'Community Alert' &&
                    str_contains($data['message'], 'Faisal Hussain mentioned everyone');
            }
        );

        Notification::assertNotSentTo(
            $this->unverifiedUser,
            GeneralNotification::class
        );

        Notification::assertNotSentTo(
            $this->verifiedUser,
            GeneralNotification::class
        );
    }
}
