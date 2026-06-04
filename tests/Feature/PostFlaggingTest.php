<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\ResidentProfile;
use App\Models\PostFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFlaggingTest extends TestCase
{
    use RefreshDatabase;

    private User $verifiedUser;
    private User $unverifiedUser;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user who is verified
        $this->verifiedUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $this->verifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        // Create a user who is not verified
        $this->unverifiedUser = User::factory()->create();

        // Create a post by another user
        $author = User::factory()->create();
        $this->post = Post::create([
            'user_id' => $author->id,
            'content' => 'This is a discussion post for testing community flagging.',
            'status' => 'published',
        ]);
    }

    /**
     * Test unverified resident is blocked from flagging.
     */
    public function test_unverified_resident_cannot_flag_post(): void
    {
        $response = $this->actingAs($this->unverifiedUser, 'sanctum')
            ->postJson("/api/posts/{$this->post->id}/flag", [
                'reason' => 'spam',
                'comment' => 'This looks like spam.',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Action locked. Residency verification required.');
    }

    /**
     * Test verified resident can flag post.
     */
    public function test_verified_resident_can_flag_post(): void
    {
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/posts/{$this->post->id}/flag", [
                'reason' => 'spam',
                'comment' => 'This is spam.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Post flagged successfully.')
            ->assertJsonPath('flags_count', 1);

        $this->assertDatabaseHas('post_flags', [
            'post_id' => $this->post->id,
            'user_id' => $this->verifiedUser->id,
            'reason' => 'spam',
            'comment' => 'This is spam.',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'flags_count' => 1,
            'status' => 'published', // Remains published since flags count is less than 5
        ]);
    }

    /**
     * Test a user cannot flag their own post.
     */
    public function test_user_cannot_flag_own_post(): void
    {
        // Author tries to flag their own post
        $author = $this->post->user;

        // Verify the author
        ResidentProfile::create([
            'user_id' => $author->id,
            'phase' => 'Phase 1',
            'block' => 'Block B',
            'house_number' => '101',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->postJson("/api/posts/{$this->post->id}/flag", [
                'reason' => 'spam',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'You cannot flag your own post.');

        $this->assertDatabaseMissing('post_flags', [
            'post_id' => $this->post->id,
            'user_id' => $author->id,
        ]);
    }

    /**
     * Test user cannot flag the same post twice.
     */
    public function test_user_cannot_flag_same_post_twice(): void
    {
        // First flag submission
        $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/posts/{$this->post->id}/flag", [
                'reason' => 'spam',
            ]);

        // Second flag submission
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/posts/{$this->post->id}/flag", [
                'reason' => 'inappropriate',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'You have already flagged this post.');

        // Flags count should still be 1
        $this->assertEquals(1, $this->post->fresh()->flags_count);
    }

    /**
     * Test auto-moderation threshold: post status transitions to 'flagged' at 5 flags.
     */
    public function test_auto_moderation_threshold_hides_post(): void
    {
        // Create 4 other verified residents
        $users = User::factory(4)->create();
        foreach ($users as $user) {
            ResidentProfile::create([
                'user_id' => $user->id,
                'phase' => 'Phase 2',
                'block' => 'Block C',
                'house_number' => '200',
                'user_type' => 'tenant',
                'is_verified' => true,
                'status' => 'approved',
            ]);
        }

        // Flag the post with the first 4 users
        foreach ($users as $user) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson("/api/posts/{$this->post->id}/flag", [
                    'reason' => 'spam',
                ]);
            $response->assertStatus(200);
        }

        $freshPost = $this->post->fresh();
        $this->assertEquals(4, $freshPost->flags_count);
        $this->assertEquals('published', $freshPost->status);

        // Flag it for the 5th time with $this->verifiedUser
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/posts/{$this->post->id}/flag", [
                'reason' => 'harassment',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'flagged');

        $finalPost = $this->post->fresh();
        $this->assertEquals(5, $finalPost->flags_count);
        $this->assertEquals('flagged', $finalPost->status);

        // Assert moderation log was created automatically
        $this->assertDatabaseHas('moderation_logs', [
            'action' => 'auto_flag_post',
            'target_type' => Post::class,
            'target_id' => $this->post->id,
            'reason' => 'Post automatically flagged due to reaching the community report threshold of 5 flags.',
        ]);
    }
}
