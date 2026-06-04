<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResidentProfile;
use App\Models\ModerationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResidencyVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup local storage disk fakes
        Storage::fake('local');
    }

    /**
     * Test a user can complete their profile and upload document proof.
     */
    public function test_user_can_submit_residency_verification_request(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('bill.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 1',
                'block' => 'Block G',
                'house_number' => '142',
                'street_number' => 'Street 4',
                'user_type' => 'tenant',
                'document' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.status', 'pending')
            ->assertJsonPath('profile.is_verified', false);

        $this->assertDatabaseHas('resident_profiles', [
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block G',
            'house_number' => '142',
            'street_number' => 'Street 4',
            'user_type' => 'tenant',
            'status' => 'pending',
            'is_verified' => false,
        ]);
    }

    /**
     * Test validation rules are enforced.
     */
    public function test_residency_submission_validates_required_fields(): void
    {
        $user = User::factory()->create();

        // Missing document and invalid phase
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 999', // Invalid enum
                'block' => '',
                'house_number' => '142',
                'user_type' => 'visitor', // Invalid enum
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phase', 'block', 'user_type', 'document']);
    }

    /**
     * Test write actions are locked for guests / unverified users.
     */
    public function test_unverified_residents_cannot_write_to_discussion_feed(): void
    {
        $user = User::factory()->create();
        
        // No profile exists, or unverified
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Trying to post something',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Action locked. Residency verification required.');
    }

    /**
     * Test write actions are unlocked for verified users.
     */
    public function test_verified_residents_can_write_to_discussion_feed(): void
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

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'This is a verified test post',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is a verified test post',
        ]);
    }

    /**
     * Test rolling rejections lock the form (3 consecutive rejections in 48h).
     */
    public function test_rolling_rejection_limit_locks_profile_submissions(): void
    {
        $user = User::factory()->create();
        
        // Setup a resident profile
        $profile = ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => false,
            'status' => 'rejected',
        ]);

        // Create 3 rejections in logs inside 48 hours
        for ($i = 0; $i < 3; $i++) {
            ModerationLog::create([
                'action' => 'reject_resident',
                'target_type' => User::class,
                'target_id' => $user->id,
                'reason' => 'blurry_document',
                'created_at' => now(),
            ]);
        }

        // Check user endpoint rate limit report
        $userRes = $this->actingAs($user, 'sanctum')->getJson('/api/user');
        $userRes->assertStatus(200)
            ->assertJsonPath('is_locked', true)
            ->assertJsonPath('rejections_count', 3);

        // Submit form should block
        $file = UploadedFile::fake()->create('bill.pdf', 500, 'application/pdf');
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 1',
                'block' => 'Block G',
                'house_number' => '142',
                'user_type' => 'owner',
                'document' => $file,
            ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Your account is locked due to too many failed verification attempts. Please visit the society office for physical verification.');
    }

    /**
     * Test secure document proxy checks permission.
     */
    public function test_admin_proxy_route_protects_verification_documents(): void
    {
        $user = User::factory()->create(); // Ordinary user (no admin role)

        $response = $this->actingAs($user, 'web')
            ->get('/admin/document-view?path=local://verification-documents/test.pdf');

        $response->assertStatus(403);
    }

    /**
     * Test residency document upload uses proper documents/{user_id}/... folder structure.
     */
    public function test_user_profile_document_upload_uses_correct_folder_path(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('my_bill.jpeg', 300, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 2',
                'block' => 'Block B',
                'house_number' => '99',
                'user_type' => 'owner',
                'document' => $file,
            ]);

        $response->assertStatus(200);

        // Fetch the created profile to inspect the path
        $profile = ResidentProfile::where('user_id', $user->id)->firstOrFail();
        
        // Path should match local fallback pattern: local://documents/{user_id}/bill_{timestamp}.jpeg
        $this->assertStringStartsWith("local://documents/{$user->id}/bill_", $profile->document_path);
        
        // Assert the file exists on local private disk at the targeted path
        $cleanLocalPath = str_replace('local://', '', $profile->document_path);
        Storage::disk('local')->assertExists($cleanLocalPath);
    }

    /**
     * Test media upload route validates user verified status and S3 disk uploads.
     */
    public function test_verified_resident_can_upload_media_to_s3_and_unverified_cannot(): void
    {
        $unverifiedUser = User::factory()->create();
        $verifiedUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $verifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        Storage::fake('public'); // Mock public fallback disk
        Storage::fake('s3'); // Mock S3 disk

        $file = UploadedFile::fake()->create('attachment.png', 200, 'image/png');

        // 1. Unverified user upload fails with 403
        $response1 = $this->actingAs($unverifiedUser, 'sanctum')
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'post',
            ]);
        $response1->assertStatus(403)
            ->assertJsonPath('message', 'Action locked. Residency verification required to upload files.');

        // 2. Verified user upload succeeds and uploads to disk
        $response2 = $this->actingAs($verifiedUser, 'sanctum')
            ->postJson('/api/media/upload', [
                'file' => $file,
                'type' => 'post',
            ]);
        $response2->assertStatus(200);

        // Should return a URL
        $url = $response2->json('url');
        $this->assertNotNull($url);

        // Assert file exists on the correct disk depending on presence of AWS env vars
        $disk = (empty(env('AWS_ACCESS_KEY_ID')) || empty(env('AWS_BUCKET'))) ? 'public' : 's3';
        Storage::disk($disk)->assertExists("posts/{$verifiedUser->id}/" . basename($url));
    }

    /**
     * Test the Artisan command app:purge-verified-documents deletes files and updates db to 'purged'.
     */
    public function test_purge_verified_documents_artisan_command_deletes_file_and_updates_db(): void
    {
        $user = User::factory()->create();
        $filename = 'bill_test.pdf';
        
        // Manually write file on fake local disk under documents/{user_id}/... folder path
        $path = "documents/{$user->id}/{$filename}";
        Storage::disk('local')->put($path, 'dummy content');
        Storage::disk('local')->assertExists($path);

        $profile = ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
            'document_path' => 'local://' . $path,
        ]);

        // Run the purge Artisan command
        $this->artisan('app:purge-verified-documents')
            ->expectsOutput('Starting verification document purge...')
            ->expectsOutput("Purging document for user {$user->id}: local://{$path}")
            ->expectsOutput('Purged 1 verification documents successfully.')
            ->assertExitCode(0);

        // Assert document path is now 'purged' in the database
        $this->assertDatabaseHas('resident_profiles', [
            'id' => $profile->id,
            'document_path' => 'purged',
        ]);

        // Assert file has been deleted from the private local disk
        Storage::disk('local')->assertMissing($path);
    }
}
