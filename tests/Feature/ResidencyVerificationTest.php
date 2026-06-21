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
        Storage::fake('s3');
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

        // Missing document (optional now) and invalid phase
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 999', // Invalid enum
                'block' => '',
                'house_number' => '142',
                'user_type' => 'visitor', // Invalid enum
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phase', 'block', 'user_type'])
            ->assertJsonMissingValidationErrors(['document']);
    }

    /**
     * Test a user can complete their profile without uploading a document initially.
     */
    public function test_user_can_submit_profile_without_document(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 1',
                'block' => 'Block G',
                'house_number' => '142',
                'street_number' => 'Street 4',
                'user_type' => 'tenant',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.is_verified', false);

        $this->assertDatabaseHas('resident_profiles', [
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block G',
            'house_number' => '142',
            'street_number' => 'Street 4',
            'user_type' => 'tenant',
            'document_path' => null,
            'is_verified' => false,
        ]);
    }

    /**
     * Test a user can upload their residency document separately.
     */
    public function test_user_can_upload_document_separately(): void
    {
        $user = User::factory()->create();
        
        // Pre-create profile without document
        ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => false,
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->create('bill.png', 500, 'image/png');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile/document', [
                'document' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.status', 'pending')
            ->assertJsonPath('profile.is_verified', false);

        $profile = ResidentProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile->document_path);
        $this->assertStringContainsString('bill_', $profile->document_path);
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
     * Test verified residents can write to discussion feed with up to 3 images.
     */
    public function test_verified_residents_can_post_with_images(): void
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

        $mediaUrls = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
            'https://example.com/image3.jpg',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'This is a post with images',
                'media_urls' => $mediaUrls,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is a post with images',
        ]);

        $post = \App\Models\Post::where('user_id', $user->id)->first();
        $this->assertEquals($mediaUrls, $post->media_urls);
    }

    /**
     * Test post creation fails with more than 3 images.
     */
    public function test_post_creation_fails_with_more_than_three_images(): void
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
                'content' => 'This has too many images',
                'media_urls' => [
                    'https://example.com/image1.jpg',
                    'https://example.com/image2.jpg',
                    'https://example.com/image3.jpg',
                    'https://example.com/image4.jpg',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['media_urls']);
    }

    /**
     * Test post creation validates format of image URLs.
     */
    public function test_post_creation_validates_image_url_format(): void
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
                'content' => 'This has invalid urls',
                'media_urls' => [
                    'not-a-valid-url',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['media_urls.0']);
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
        // Explicitly clear S3 credentials to force local fallback behavior
        config([
            'filesystems.disks.s3.key' => null,
            'filesystems.disks.s3.bucket' => null,
        ]);

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

    /**
     * Test residency document upload uses S3 when configuration is present.
     */
    public function test_user_profile_document_upload_uses_s3_when_configured(): void
    {
        config([
            'filesystems.disks.s3.key' => 'test-key-id',
            'filesystems.disks.s3.bucket' => 'test-bucket',
        ]);

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

        $profile = ResidentProfile::where('user_id', $user->id)->firstOrFail();
        
        // Path should match S3 pattern: s3://documents/{user_id}/bill_{timestamp}.jpeg
        $this->assertStringStartsWith("s3://documents/{$user->id}/bill_", $profile->document_path);
        
        // Assert the file exists on s3 fake disk
        $cleanS3Path = str_replace('s3://', '', $profile->document_path);
        Storage::disk('s3')->assertExists($cleanS3Path);
    }

    /**
     * Test a notification is sent to superadmin and community-admin when residency verification is submitted,
     * and the notification data is fully compatible with Filament database notification panel.
     */
    public function test_submitting_verification_notifies_staff_with_filament_compatible_data(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create a superadmin and a community admin
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $communityAdmin = User::factory()->create();
        $communityAdmin->assignRole('community-admin');

        // Create a regular user who will submit
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

        $response->assertStatus(200);

        // Verify that notifications are created in database for superadmin and community admin
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $superadmin->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $communityAdmin->id,
        ]);

        // Get the notification data and assert compatibility
        $notification = $superadmin->notifications()->first();
        $this->assertNotNull($notification);

        $data = $notification->data;
        $this->assertEquals('Resident Verification Submitted', $data['title']);
        $this->assertEquals("{$user->name} has submitted proof of residency for verification.", $data['body']);
        $this->assertEquals('heroicon-o-shield-check', $data['icon']);
        $this->assertEquals('warning', $data['iconColor']);
        $this->assertEquals('warning', $data['status']);
        $this->assertEquals('persistent', $data['duration']);
        $this->assertEquals('filament', $data['format']);
        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(1, $data['actions']);
        $this->assertEquals('view', $data['actions'][0]['name']);
        $this->assertEquals('/admin/resident-profiles', $data['actions'][0]['url']);
        $this->assertEquals('filament::components.link', $data['actions'][0]['view']);
        $this->assertEquals('moderation_verification', $data['metadata']['type']);
        $this->assertEquals($user->id, $data['metadata']['user_id']);
    }

    /**
     * Test residency document is deleted immediately when approved.
     */
    public function test_document_is_deleted_immediately_on_approval(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $filename = 'bill_test.pdf';
        $path = "documents/{$user->id}/{$filename}";
        Storage::disk('local')->put($path, 'dummy content');
        Storage::disk('local')->assertExists($path);

        $profile = ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => false,
            'status' => 'pending',
            'document_path' => 'local://' . $path,
        ]);

        // Verify it was created and exists
        $this->assertEquals('local://' . $path, $profile->document_path);

        // Approve the profile (update status to approved)
        $profile->update([
            'status' => 'approved',
            'is_verified' => true,
        ]);

        // Assert file has been deleted from local storage
        Storage::disk('local')->assertMissing($path);

        // Assert document path is now 'purged' in the database
        $this->assertDatabaseHas('resident_profiles', [
            'id' => $profile->id,
            'status' => 'approved',
            'document_path' => 'purged',
        ]);
    }

    /**
     * Test residency document is deleted immediately when rejected.
     */
    public function test_document_is_deleted_immediately_on_rejection(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $filename = 'bill_test.pdf';
        $path = "documents/{$user->id}/{$filename}";
        Storage::disk('local')->put($path, 'dummy content');
        Storage::disk('local')->assertExists($path);

        $profile = ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => false,
            'status' => 'pending',
            'document_path' => 'local://' . $path,
        ]);

        // Verify it was created and exists
        $this->assertEquals('local://' . $path, $profile->document_path);

        // Reject the profile (update status to rejected)
        $profile->update([
            'status' => 'rejected',
            'rejection_reason' => 'blurry_document',
        ]);

        // Assert file has been deleted from local storage
        Storage::disk('local')->assertMissing($path);

        // Assert document path is now 'purged' in the database
        $this->assertDatabaseHas('resident_profiles', [
            'id' => $profile->id,
            'status' => 'rejected',
            'document_path' => 'purged',
        ]);
    }

    /**
     * Test toggling residency verification settings.
     */
    public function test_residency_verification_toggle_flow(): void
    {
        // 1. Turn residency verification OFF
        \App\Models\Setting::setValue('residency_verification_enabled', false);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Assert User::isResidencyVerified returns true even without a profile
        $this->assertTrue($user->isResidencyVerified());

        // Assert we can submit profile and it gets auto-verified instantly
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/resident/profile', [
                'phase' => 'Phase 1',
                'block' => 'Block A',
                'house_number' => '100',
                'user_type' => 'owner',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('resident_profiles', [
            'user_id' => $user->id,
            'is_verified' => true,
            'status' => 'approved',
        ]);

        // Assert user can post to the feed
        $postResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Test content when verification is disabled',
            ]);
        $postResponse->assertStatus(201);

        // Assert serialized user output has residency_verification_enabled false and overrides is_verified/status
        $userResponse = $this->actingAs($user, 'sanctum')->getJson('/api/user');
        $userResponse->assertStatus(200)
            ->assertJsonPath('user.residency_verification_enabled', false)
            ->assertJsonPath('user.resident_profile.is_verified', true)
            ->assertJsonPath('user.resident_profile.status', 'approved');

        // Assert verified residents query matches all active verified email users
        $verifiedCount = User::verifiedResidents()->count();
        $this->assertEquals(1, $verifiedCount);

        // 2. Turn residency verification back ON
        \App\Models\Setting::setValue('residency_verification_enabled', true);

        // Delete the profile to test fresh logic
        $user->residentProfile()->delete();
        $user->unsetRelation('residentProfile');

        // Now, isResidencyVerified should return false because we don't have a profile
        $this->assertFalse($user->isResidencyVerified());

        // Post should now fail with 403
        $postResponse2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Should fail now',
            ]);
        $postResponse2->assertStatus(403);
    }
}
