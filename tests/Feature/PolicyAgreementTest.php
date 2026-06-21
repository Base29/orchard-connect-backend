<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResidentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PolicyAgreementTest extends TestCase
{
    use RefreshDatabase;

    private User $normalUserWithoutConsent;
    private User $normalUserWithConsent;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup roles if needed
        Role::firstOrCreate(['name' => 'superadmin']);

        // 1. Normal user who hasn't accepted policies
        $this->normalUserWithoutConsent = User::factory()->create([
            'email_verified_at' => now(),
            'policies_accepted' => false,
            'policies_accepted_at' => null,
        ]);

        // 2. Normal user who has accepted policies
        $this->normalUserWithConsent = User::factory()->create([
            'email_verified_at' => now(),
            'policies_accepted' => true,
            'policies_accepted_at' => now(),
        ]);

        // 3. Super admin (exempt from policy requirements)
        $this->superAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'policies_accepted' => false,
            'policies_accepted_at' => null,
        ]);
        $this->superAdmin->assignRole('superadmin');
    }

    /**
     * Test traditional registration requires policies acceptance.
     */
    public function test_traditional_registration_requires_policies_acceptance(): void
    {
        // Without checking policies box
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Resident',
            'email' => 'newres@orchard.local',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['policies_accepted']);

        // With checking policies box
        $response2 = $this->postJson('/api/auth/register', [
            'name' => 'New Resident',
            'email' => 'newres@orchard.local',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'policies_accepted' => true,
        ]);

        $response2->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'newres@orchard.local',
            'policies_accepted' => true,
        ]);
    }

    /**
     * Users who haven't accepted policies are blocked from regular endpoints.
     */
    public function test_user_without_consent_is_blocked(): void
    {
        $response = $this->actingAs($this->normalUserWithoutConsent, 'sanctum')
            ->getJson('/api/user/stats');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'POLICIES_NOT_ACCEPTED',
                'message' => 'You must agree to the platform policies to continue.'
            ]);
    }

    /**
     * Users who have accepted policies are not blocked.
     */
    public function test_user_with_consent_is_not_blocked(): void
    {
        $response = $this->actingAs($this->normalUserWithConsent, 'sanctum')
            ->getJson('/api/user/stats');

        $response->assertStatus(200);
    }

    /**
     * Superadmins bypass the policy acceptance block.
     */
    public function test_superadmin_bypasses_consent_block(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/user/stats');

        // Should return 200 (stats return 200, though they might be empty)
        $response->assertStatus(200);
    }

    /**
     * Users without consent can still query /api/user.
     */
    public function test_user_without_consent_can_query_self(): void
    {
        $response = $this->actingAs($this->normalUserWithoutConsent, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('user.policies_accepted', false);
    }

    /**
     * Users without consent can still query /api/broadcasting/auth.
     */
    public function test_user_without_consent_can_access_broadcasting_auth(): void
    {
        $response = $this->actingAs($this->normalUserWithoutConsent, 'sanctum')
            ->postJson('/api/broadcasting/auth', [
                'channel_name' => 'private-App.Models.User.' . $this->normalUserWithoutConsent->id,
                'socket_id' => '1234.5678',
            ]);

        $response->assertStatus(200);
    }

    /**
     * Users can accept policies on the `/api/policies/accept` route.
     */
    public function test_user_can_accept_policies_on_endpoint(): void
    {
        $response = $this->actingAs($this->normalUserWithoutConsent, 'sanctum')
            ->postJson('/api/policies/accept');

        $response->assertStatus(200)
            ->assertJsonPath('user.policies_accepted', true);

        $this->assertDatabaseHas('users', [
            'id' => $this->normalUserWithoutConsent->id,
            'policies_accepted' => true,
        ]);

        // Next request should now succeed
        $response2 = $this->actingAs($this->normalUserWithoutConsent, 'sanctum')
            ->getJson('/api/user/stats');

        $response2->assertStatus(200);
    }
}
