<?php

namespace Tests\Feature;

use App\Models\PhoneDirectory;
use App\Models\User;
use App\Models\ResidentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed a few contact entries
        PhoneDirectory::create([
            'name' => 'Security Office',
            'phone_number' => '+92 300 1234567',
            'category' => 'Security',
            'order' => 1,
            'description' => 'Main gate entry coordination.',
        ]);

        PhoneDirectory::create([
            'name' => 'Ambulance',
            'phone_number' => '1122',
            'category' => 'Emergency & Health',
            'order' => 1,
            'description' => 'Rescue services.',
        ]);
    }

    /**
     * Unauthenticated guest receives 401 when fetching directory.
     */
    public function test_guest_cannot_access_phone_directory(): void
    {
        $response = $this->getJson('/api/phone-directory');
        $response->assertStatus(401);
    }

    /**
     * User with no resident profile receives 403.
     */
    public function test_user_without_profile_cannot_access_phone_directory(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/phone-directory');
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Action locked. Residency verification required.');
    }

    /**
     * User with unverified/pending resident profile receives 403.
     */
    public function test_unverified_resident_cannot_access_phone_directory(): void
    {
        $user = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '123',
            'user_type' => 'owner',
            'document_path' => 'bill.jpg',
            'status' => 'pending',
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/phone-directory');
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Action locked. Residency verification required.');
    }

    /**
     * Verified resident can access phone directory and gets data.
     */
    public function test_verified_resident_can_access_phone_directory(): void
    {
        $user = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '123',
            'user_type' => 'owner',
            'document_path' => 'bill.jpg',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/phone-directory');
        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'name' => 'Security Office',
                'phone_number' => '+92 300 1234567',
                'category' => 'Security',
            ]);
    }
}
