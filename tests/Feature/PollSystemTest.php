<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use App\Models\ResidentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $verifiedUser;
    private User $unverifiedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->verifiedUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $this->verifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'document_path' => 'bill.png',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        $this->unverifiedUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $this->unverifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block B',
            'house_number' => '101',
            'user_type' => 'owner',
            'document_path' => 'bill2.png',
            'status' => 'pending',
            'is_verified' => false,
        ]);
    }

    /**
     * Unauthenticated guest cannot access polls.
     */
    public function test_guest_cannot_access_polls(): void
    {
        $this->getJson('/api/polls')->assertStatus(401);
    }

    /**
     * Unverified resident cannot create a poll.
     */
    public function test_unverified_resident_cannot_create_poll(): void
    {
        $payload = [
            'title' => 'Test Poll',
            'description' => 'Poll Description',
            'start_at' => now()->toIso8601String(),
            'end_at' => now()->addDays(7)->toIso8601String(),
            'options' => ['Option A', 'Option B'],
        ];

        $this->actingAs($this->unverifiedUser)
            ->postJson('/api/polls', $payload)
            ->assertStatus(403)
            ->assertJsonPath('message', 'Action locked. Residency verification required.');
    }

    /**
     * Verified resident can create a poll.
     */
    public function test_verified_resident_can_create_poll(): void
    {
        $payload = [
            'title' => 'Community Park Renovations',
            'description' => 'Should we add a jogging track?',
            'start_at' => now()->toIso8601String(),
            'end_at' => now()->addDays(7)->toIso8601String(),
            'options' => ['Yes, absolutely', 'No, not needed'],
        ];

        $this->actingAs($this->verifiedUser)
            ->postJson('/api/polls', $payload)
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'title', 'options']);

        $this->assertDatabaseHas('polls', [
            'title' => 'Community Park Renovations',
            'user_id' => $this->verifiedUser->id,
        ]);

        $this->assertDatabaseHas('poll_options', [
            'option_text' => 'Yes, absolutely',
        ]);
    }

    /**
     * Verified resident cannot create a new poll if they already have an active poll running.
     */
    public function test_resident_cannot_create_poll_with_active_running_poll(): void
    {
        // First, create an active poll
        Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Active Poll 1',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $payload = [
            'title' => 'Active Poll 2',
            'description' => 'Another active poll',
            'start_at' => now()->toIso8601String(),
            'end_at' => now()->addDays(7)->toIso8601String(),
            'options' => ['Option A', 'Option B'],
        ];

        // Should fail
        $this->actingAs($this->verifiedUser)
            ->postJson('/api/polls', $payload)
            ->assertStatus(422)
            ->assertJsonPath('message', 'You already have an active poll running. You cannot create a new poll until it finishes or is stopped.');
    }

    /**
     * Resident CAN create a poll if their previous poll is NOT active (e.g. finished or suspended).
     */
    public function test_resident_can_create_poll_if_previous_poll_is_inactive(): void
    {
        // Create a finished poll
        Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Finished Poll',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $payload = [
            'title' => 'New Active Poll',
            'description' => 'New poll',
            'start_at' => now()->toIso8601String(),
            'end_at' => now()->addDays(7)->toIso8601String(),
            'options' => ['Option A', 'Option B'],
        ];

        // Should succeed
        $this->actingAs($this->verifiedUser)
            ->postJson('/api/polls', $payload)
            ->assertStatus(201);
    }

    /**
     * Verified resident can vote on active polls.
     */
    public function test_verified_resident_can_vote_on_active_poll(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Active Poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $option = PollOption::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'poll_id' => $poll->id,
            'option_text' => 'Option A',
        ]);

        $this->actingAs($this->verifiedUser)
            ->postJson("/api/polls/{$poll->id}/vote", [
                'poll_option_id' => $option->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('votes_count', 1);

        $this->assertDatabaseHas('poll_votes', [
            'poll_id' => $poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $this->verifiedUser->id,
        ]);
    }

    /**
     * User cannot vote twice on the same poll.
     */
    public function test_user_cannot_vote_twice(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Active Poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $option = PollOption::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'poll_id' => $poll->id,
            'option_text' => 'Option A',
        ]);

        // First vote
        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $this->verifiedUser->id,
        ]);

        // Second vote should fail
        $this->actingAs($this->verifiedUser)
            ->postJson("/api/polls/{$poll->id}/vote", [
                'poll_option_id' => $option->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You have already voted in this poll.');
    }

    /**
     * Active polls cannot be edited.
     */
    public function test_active_polls_cannot_be_edited(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Active Poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this->actingAs($this->verifiedUser)
            ->putJson("/api/polls/{$poll->id}", [
                'title' => 'Updated Poll Name',
                'start_at' => now()->toIso8601String(),
                'end_at' => now()->addDays(5)->toIso8601String(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Active polls cannot be edited.');
    }

    /**
     * Inactive (e.g. future start date) polls CAN be edited by the creator.
     */
    public function test_inactive_polls_can_be_edited_by_creator(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Future Poll',
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->actingAs($this->verifiedUser)
            ->putJson("/api/polls/{$poll->id}", [
                'title' => 'Updated Future Poll Name',
                'start_at' => now()->addDays(5)->toIso8601String(),
                'end_at' => now()->addDays(15)->toIso8601String(),
            ])
            ->assertStatus(200)
            ->assertJsonPath('title', 'Updated Future Poll Name');
    }

    /**
     * Moderator or creator can stop/suspend a poll.
     */
    public function test_creator_can_suspend_poll(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Active Poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this->actingAs($this->verifiedUser)
            ->postJson("/api/polls/{$poll->id}/suspend")
            ->assertStatus(200)
            ->assertJsonPath('status', 'suspended');

        $this->assertDatabaseHas('polls', [
            'id' => $poll->id,
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('moderation_logs', [
            'action' => 'suspend_poll',
            'target_id' => $poll->id,
        ]);
    }

    /**
     * Poll creator can retrieve voters list.
     */
    public function test_poll_creator_can_retrieve_voters_list(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Creator Poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $option = PollOption::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'poll_id' => $poll->id,
            'option_text' => 'Option A',
        ]);

        $voter = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $voter->id,
            'phase' => 'Phase 2',
            'block' => 'Block C',
            'house_number' => '200',
            'user_type' => 'owner',
            'document_path' => 'doc.png',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $voter->id,
        ]);

        $response = $this->actingAs($this->verifiedUser)
            ->getJson('/api/polls')
            ->assertStatus(200);

        // Assert that the first poll returned (Creator Poll) has the votes key with details
        $pollsData = $response->json('data');
        $creatorPoll = collect($pollsData)->firstWhere('id', $poll->id);

        $this->assertNotNull($creatorPoll);
        $this->assertArrayHasKey('votes', $creatorPoll);
        $this->assertCount(1, $creatorPoll['votes']);
        $this->assertEquals($voter->name, $creatorPoll['votes'][0]['user']['name']);
        $this->assertEquals('Phase 2', $creatorPoll['votes'][0]['user']['resident_profile']['phase']);
        $this->assertEquals('Option A', $creatorPoll['votes'][0]['option']['option_text']);
    }

    /**
     * Non-creator and non-moderator cannot retrieve voters list.
     */
    public function test_non_creator_cannot_retrieve_voters_list(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Creator Poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $option = PollOption::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'poll_id' => $poll->id,
            'option_text' => 'Option A',
        ]);

        $voter = User::factory()->create();
        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $voter->id,
        ]);

        $otherUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $otherUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '102',
            'user_type' => 'owner',
            'document_path' => 'doc.png',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        $response = $this->actingAs($otherUser)
            ->getJson('/api/polls')
            ->assertStatus(200);

        $pollsData = $response->json('data');
        $creatorPoll = collect($pollsData)->firstWhere('id', $poll->id);

        $this->assertNotNull($creatorPoll);
        $this->assertArrayNotHasKey('votes', $creatorPoll);
    }

    /**
     * Verified resident can create an anonymous poll.
     */
    public function test_verified_resident_can_create_anonymous_poll(): void
    {
        $payload = [
            'title' => 'Anonymous Park Feedback',
            'description' => 'Is the lighting sufficient?',
            'start_at' => now()->toIso8601String(),
            'end_at' => now()->addDays(7)->toIso8601String(),
            'options' => ['Yes', 'No'],
            'is_anonymous' => true,
        ];

        $this->actingAs($this->verifiedUser)
            ->postJson('/api/polls', $payload)
            ->assertStatus(201)
            ->assertJsonPath('is_anonymous', true);

        $this->assertDatabaseHas('polls', [
            'title' => 'Anonymous Park Feedback',
            'user_id' => $this->verifiedUser->id,
            'is_anonymous' => true,
        ]);
    }

    /**
     * Anonymous poll does not expose voters list to anyone, including creator or admins.
     */
    public function test_anonymous_poll_does_not_expose_voters_list_to_anyone(): void
    {
        $poll = Poll::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->verifiedUser->id,
            'title' => 'Secret Poll',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'status' => 'active',
            'is_anonymous' => true,
        ]);

        $option = PollOption::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'poll_id' => $poll->id,
            'option_text' => 'Yes',
        ]);

        $voter = User::factory()->create();
        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $voter->id,
        ]);

        // Check response for creator
        $responseCreator = $this->actingAs($this->verifiedUser)
            ->getJson('/api/polls')
            ->assertStatus(200);

        $pollsDataCreator = $responseCreator->json('data');
        $creatorPollObj = collect($pollsDataCreator)->firstWhere('id', $poll->id);

        $this->assertNotNull($creatorPollObj);
        $this->assertArrayNotHasKey('votes', $creatorPollObj);

        // Check response for super admin/moderator
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        
        $responseAdmin = $this->actingAs($admin)
            ->getJson('/api/polls')
            ->assertStatus(200);

        $pollsDataAdmin = $responseAdmin->json('data');
        $adminPollObj = collect($pollsDataAdmin)->firstWhere('id', $poll->id);

        $this->assertNotNull($adminPollObj);
        $this->assertArrayNotHasKey('votes', $adminPollObj);
    }
}


