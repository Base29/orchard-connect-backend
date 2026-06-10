<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Listing;
use App\Models\ResidentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guest users cannot access the stats API.
     */
    public function test_guest_cannot_access_stats(): void
    {
        $response = $this->getJson('/api/user/stats');
        $response->assertStatus(401);
    }

    /**
     * Test stats are returned correctly for a user with no activity.
     */
    public function test_user_with_no_activity_returns_zero_stats(): void
    {
        $user = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/stats');

        $response->assertStatus(200)
            ->assertJson([
                'posts' => [
                    'count' => 0,
                    'likes' => 0,
                    'comments' => 0,
                ],
                'polls' => [
                    'count' => 0,
                    'votes' => 0,
                ],
                'ads' => [
                    'count' => 0,
                    'active' => 0,
                    'sold' => 0,
                ]
            ]);
    }

    /**
     * Test stats are calculated and aggregated correctly.
     */
    public function test_stats_are_aggregated_correctly(): void
    {
        $user = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        $otherUser = User::factory()->create();

        // 1. Posts & Likes & Comments setup
        // Create 2 published posts for our user
        $post1 = Post::create([
            'user_id' => $user->id,
            'content' => 'Post 1 content',
            'status' => 'published',
        ]);
        $post2 = Post::create([
            'user_id' => $user->id,
            'content' => 'Post 2 content',
            'status' => 'published',
        ]);
        // Create 1 draft post (should not be counted in stats)
        Post::create([
            'user_id' => $user->id,
            'content' => 'Draft post',
            'status' => 'draft',
        ]);
        // Create 1 post for other user (should not be counted)
        Post::create([
            'user_id' => $otherUser->id,
            'content' => 'Other user post',
            'status' => 'published',
        ]);

        // Add 3 likes in total to our user's posts
        $post1->likes()->create(['user_id' => $user->id]);
        $post1->likes()->create(['user_id' => $otherUser->id]);
        $post2->likes()->create(['user_id' => $otherUser->id]);

        // Add 2 comments to post1, 1 comment to post2
        $post1->comments()->create(['user_id' => $otherUser->id, 'content' => 'Nice!']);
        $post1->comments()->create(['user_id' => $user->id, 'content' => 'Thanks']);
        $post2->comments()->create(['user_id' => $otherUser->id, 'content' => 'Cool']);

        // 2. Polls setup
        // Create 2 polls for our user
        $poll1 = Poll::create([
            'user_id' => $user->id,
            'title' => 'Poll 1',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);
        $poll2 = Poll::create([
            'user_id' => $user->id,
            'title' => 'Poll 2',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);
        // Create 1 poll for other user (should not be counted)
        $otherPoll = Poll::create([
            'user_id' => $otherUser->id,
            'title' => 'Other poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        // Add options & votes
        $option1 = $poll1->options()->create(['option_text' => 'Option 1']);
        $option2 = $poll1->options()->create(['option_text' => 'Option 2']);
        $option3 = $poll2->options()->create(['option_text' => 'Option 3']);
        
        $otherOption = $otherPoll->options()->create(['option_text' => 'Other Option']);

        // Cast 2 votes on poll1, 1 vote on poll2
        PollVote::create(['poll_id' => $poll1->id, 'poll_option_id' => $option1->id, 'user_id' => $user->id]);
        PollVote::create(['poll_id' => $poll1->id, 'poll_option_id' => $option2->id, 'user_id' => $otherUser->id]);
        PollVote::create(['poll_id' => $poll2->id, 'poll_option_id' => $option3->id, 'user_id' => $otherUser->id]);
        // Vote on other poll (should not be counted)
        PollVote::create(['poll_id' => $otherPoll->id, 'poll_option_id' => $otherOption->id, 'user_id' => $user->id]);

        // 3. Classified Ads (listings) setup
        // Create 3 listings (1 active, 1 sold, 1 pending)
        Listing::create([
            'user_id' => $user->id,
            'title' => 'Active Listing',
            'description' => 'Selling active item',
            'price' => 1500,
            'category' => 'Electronics',
            'contact_whatsapp' => '923001234567',
            'status' => 'active',
        ]);
        Listing::create([
            'user_id' => $user->id,
            'title' => 'Sold Listing',
            'description' => 'Sold item',
            'price' => 2000,
            'category' => 'Vehicles',
            'contact_whatsapp' => '923001234567',
            'status' => 'sold',
        ]);
        Listing::create([
            'user_id' => $user->id,
            'title' => 'Pending Listing',
            'description' => 'Pending item',
            'price' => 300,
            'category' => 'Books',
            'contact_whatsapp' => '923001234567',
            'status' => 'pending',
        ]);
        // Create listing for other user (should not be counted)
        Listing::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Listing',
            'description' => 'Other item',
            'price' => 500,
            'category' => 'Books',
            'contact_whatsapp' => '923001234567',
            'status' => 'active',
        ]);

        // Request stats
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/stats');

        $response->assertStatus(200)
            ->assertJson([
                'posts' => [
                    'count' => 2, // 2 published, draft is ignored
                    'likes' => 3, // 2 on post1 + 1 on post2
                    'comments' => 3, // 2 on post1 + 1 on post2
                ],
                'polls' => [
                    'count' => 2, // 2 created by user
                    'votes' => 3, // 2 on poll1 + 1 on poll2
                ],
                'ads' => [
                    'count' => 3, // total listings (active, sold, pending)
                    'active' => 1,
                    'sold' => 1,
                ]
            ]);
    }
}
