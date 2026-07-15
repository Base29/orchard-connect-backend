<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Invitation;
use App\Models\ResidentProfile;
use App\Models\ModerationLog;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecureInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles & permissions for testing
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /**
     * Test admin and superadmin can generate invitation links.
     */
    public function test_admin_can_create_invitation_code(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('community-admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/invitations');

        $response->assertStatus(201)
            ->assertJsonStructure(['code', 'expires_at', 'invite_url']);

        $invitation = Invitation::first();
        $this->assertNotNull($invitation);
        $this->assertEquals($admin->id, $invitation->invited_by);
        $this->assertNull($invitation->registered_user_id);
    }

    /**
     * Test ordinary residents are prohibited from generating invitation links.
     */
    public function test_invitation_access_policies_restrict_ordinary_residents_from_generating_links(): void
    {
        $user = User::factory()->create(); // Ordinary resident (no admin role)

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/invitations');

        $response->assertStatus(403);
    }

    /**
     * Test validation of invitation codes.
     */
    public function test_validation_of_active_and_invalid_invitation_codes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $invitation = Invitation::create([
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        // 1. Test valid code
        $response = $this->getJson("/api/invitations/validate/{$invitation->code}");
        $response->assertStatus(200)
            ->assertJsonPath('valid', true);

        // 2. Test non-existent code
        $responseInvalid = $this->getJson("/api/invitations/validate/nonexistentcode");
        $responseInvalid->assertStatus(400)
            ->assertJsonPath('valid', false);

        // 3. Test expired code
        $expiredInvitation = Invitation::create([
            'invited_by' => $admin->id,
            'expires_at' => now()->subDay(),
        ]);
        $responseExpired = $this->getJson("/api/invitations/validate/{$expiredInvitation->code}");
        $responseExpired->assertStatus(400)
            ->assertJsonPath('valid', false);
    }

    /**
     * Test traditional signup with invite code links the record automatically.
     */
    public function test_traditional_registration_with_invitation_code_links_record(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('community-admin');

        $invitation = Invitation::create([
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Invited Resident',
            'email' => 'invited@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'policies_accepted' => true,
            'invite_code' => $invitation->code,
        ]);

        $response->assertStatus(201);
        
        $newUser = User::where('email', 'invited@example.com')->first();
        $this->assertNotNull($newUser);

        // Verify invitation is linked
        $invitation->refresh();
        $this->assertEquals($newUser->id, $invitation->registered_user_id);
    }

    /**
     * Test claiming code post-auth (for OAuth or post-registration flows).
     */
    public function test_claiming_invitation_code_succeeds(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('community-admin');

        $invitation = Invitation::create([
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/invitations/claim', [
                'code' => $invitation->code,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Invitation successfully claimed.');

        $invitation->refresh();
        $this->assertEquals($user->id, $invitation->registered_user_id);
    }

    /**
     * Test profile submission auto-verifies when invitation is linked.
     */
    public function test_profile_submission_with_invitation_code_auto_verifies_residency_without_document(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('community-admin');

        $user = User::factory()->create();
        
        // Link user to invitation
        $invitation = Invitation::create([
            'invited_by' => $admin->id,
            'registered_user_id' => $user->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Submit profile without document
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 1',
                'block' => 'Block G',
                'house_number' => '142',
                'street_number' => 'Street 4',
                'user_type' => 'tenant',
            ]);

        $response->assertStatus(200);

        // Verify profile is approved and verified instantly
        $profile = ResidentProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($profile->is_verified);
        $this->assertEquals('approved', $profile->status);
        $this->assertEquals($admin->id, $profile->verified_by);

        // Verify a moderation log entry is stored
        $this->assertDatabaseHas('moderation_logs', [
            'action' => 'verify_resident',
            'target_type' => User::class,
            'target_id' => $user->id,
            'moderator_id' => $admin->id,
            'reason' => 'Resident automatically verified via secure invitation link.',
        ]);
    }

    /**
     * Test claiming invitation code automatically verifies any pre-existing unverified profile.
     */
    public function test_claiming_invitation_code_auto_verifies_existing_unverified_profile(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('community-admin');

        $user = User::factory()->create();
        
        // Create pre-existing pending resident profile
        $profile = ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block G',
            'house_number' => '142',
            'street_number' => 'Street 4',
            'user_type' => 'tenant',
            'is_verified' => false,
            'status' => 'pending',
        ]);

        $invitation = Invitation::create([
            'invited_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Claim invitation
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/invitations/claim', [
                'code' => $invitation->code,
            ]);

        $response->assertStatus(200);

        // Verify existing profile has been updated to approved & verified
        $profile->refresh();
        $this->assertTrue($profile->is_verified);
        $this->assertEquals('approved', $profile->status);
        $this->assertEquals($admin->id, $profile->verified_by);

        // Verify a moderation log entry is stored
        $this->assertDatabaseHas('moderation_logs', [
            'action' => 'verify_resident',
            'target_type' => User::class,
            'target_id' => $user->id,
            'moderator_id' => $admin->id,
            'reason' => 'Resident automatically verified upon claiming secure invitation link.',
        ]);
    }

    /**
     * Test invitation creation and claims are logged in activity logs.
     */
    public function test_invitation_events_are_tracked_in_activity_log(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        // 1. Track creation
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/invitations');
        
        $response->assertStatus(201);
        $invitation = Invitation::first();

        // Verify activity log has creation entry
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Invitation::class,
            'subject_id' => $invitation->id,
            'causer_id' => $admin->id,
            'event' => 'created',
        ]);

        // 2. Track claim/update
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/invitations/claim', [
                'code' => $invitation->code,
            ]);

        // Verify activity log has update entry
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Invitation::class,
            'subject_id' => $invitation->id,
            'causer_id' => $user->id,
            'event' => 'updated',
        ]);
    }
}
