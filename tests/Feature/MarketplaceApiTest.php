<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Listing;
use App\Models\ResidentProfile;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $verifiedUser;
    private User $unverifiedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a verified resident
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

        // Create an unverified resident
        $this->unverifiedUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $this->unverifiedUser->id,
            'phase' => 'Phase 2',
            'block' => 'Block B',
            'house_number' => '200',
            'user_type' => 'tenant',
            'is_verified' => false,
            'status' => 'pending',
        ]);
    }

    /**
     * Unverified residents are blocked from posting ads.
     */
    public function test_unverified_resident_cannot_create_listing(): void
    {
        $response = $this->actingAs($this->unverifiedUser, 'sanctum')
            ->postJson('/api/listings', [
                'title' => 'iPhone 15 Pro',
                'description' => 'Mint condition, 256GB storage.',
                'price' => 120000,
                'category' => 'Electronics',
                'contact_whatsapp' => '+923001234567',
                'images' => [],
            ]);

        $response->assertStatus(403);
    }

    /**
     * Verified residents can post ads.
     */
    public function test_verified_resident_can_create_listing(): void
    {
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson('/api/listings', [
                'title' => 'iPhone 15 Pro',
                'description' => 'Mint condition, 256GB storage.',
                'price' => 120000.50,
                'category' => 'Electronics',
                'contact_whatsapp' => '+923001234567',
                'images' => ['https://supabase.co/storage/v1/object/public/listings/1.jpg'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'iPhone 15 Pro')
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('listings', [
            'user_id' => $this->verifiedUser->id,
            'title' => 'iPhone 15 Pro',
            'price' => 120000.50,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'status' => 'pending',
        ]);
    }

    /**
     * Users can query their own listings, including pending ones.
     */
    public function test_user_can_retrieve_own_pending_listings(): void
    {
        // Create a pending listing for the verified user
        Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'My Pending Laptop',
            'description' => 'Not approved yet.',
            'price' => 50000,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'pending',
        ]);

        // Create an active listing for another user
        $anotherUser = User::factory()->create();
        Listing::create([
            'user_id' => $anotherUser->id,
            'title' => 'Other Active Phone',
            'description' => 'Approved.',
            'price' => 80000,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        // Query verified user's own listings
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson("/api/listings?user_id={$this->verifiedUser->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My Pending Laptop');
    }

    /**
     * Creating an ad with more than 3 images should fail with a 422 error.
     */
    public function test_create_listing_fails_with_more_than_three_images(): void
    {
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson('/api/listings', [
                'title' => 'iPhone 15 Pro',
                'description' => 'Mint condition, 256GB storage.',
                'price' => 120000.50,
                'category' => 'Electronics',
                'contact_whatsapp' => '+923001234567',
                'images' => [
                    'https://supabase.co/storage/v1/object/public/listings/1.jpg',
                    'https://supabase.co/storage/v1/object/public/listings/2.jpg',
                    'https://supabase.co/storage/v1/object/public/listings/3.jpg',
                    'https://supabase.co/storage/v1/object/public/listings/4.jpg',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images']);
    }

    /**
     * Check listings query filtering by category.
     */
    public function test_listings_can_be_filtered_by_category(): void
    {
        // Create an electronics listing
        Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Laptop Dell',
            'description' => 'Dell Latitude laptop.',
            'price' => 45000,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        // Create a vehicles listing
        Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Honda Civic',
            'description' => 'Civic 2022 model.',
            'price' => 6500000,
            'category' => 'Vehicles',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson('/api/listings?category=Electronics');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laptop Dell');
    }

    /**
     * Check listings search functionality.
     */
    public function test_listings_can_be_searched(): void
    {
        Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Wooden Sofa Set',
            'description' => 'Vintage dining/lounge sofa.',
            'price' => 35000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Toyota Corolla',
            'description' => 'Beautiful red car.',
            'price' => 4500000,
            'category' => 'Vehicles',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson('/api/listings?search=sofa');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Wooden Sofa Set');
    }

    /**
     * Owner can delete their listing.
     */
    public function test_owner_can_delete_their_listing(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'MacBook Pro',
            'description' => 'Specs M1 Max 32GB.',
            'price' => 300000,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->deleteJson("/api/listings/{$listing->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
    }

    /**
     * Non-owner cannot delete another resident's listing.
     */
    public function test_non_owner_cannot_delete_listing(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'MacBook Pro',
            'description' => 'Specs M1 Max 32GB.',
            'price' => 300000,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $anotherResident = User::factory()->create();

        $response = $this->actingAs($anotherResident, 'sanctum')
            ->deleteJson("/api/listings/{$listing->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('listings', ['id' => $listing->id]);
    }

    /**
     * Owner can change the listing status (e.g. mark as sold).
     */
    public function test_owner_can_update_listing_status(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'iPhone 13',
            'description' => '128gb.',
            'price' => 95000,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->patchJson("/api/listings/{$listing->id}/status", [
                'status' => 'sold',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'sold');

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'status' => 'sold',
        ]);
    }

    /**
     * Verified residents can post comments on ads.
     */
    public function test_verified_user_can_comment_on_listing(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Fitted Wardrobe',
            'description' => 'Wooden wardrobe.',
            'price' => 25000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/listings/{$listing->id}/comments", [
                'content' => 'Is this still available?',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('content', 'Is this still available?');

        $this->assertDatabaseHas('comments', [
            'listing_id' => $listing->id,
            'user_id' => $this->verifiedUser->id,
            'content' => 'Is this still available?',
        ]);
    }

    /**
     * Unverified residents cannot post comments on ads.
     */
    public function test_unverified_user_cannot_comment_on_listing(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Fitted Wardrobe',
            'description' => 'Wooden wardrobe.',
            'price' => 25000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->unverifiedUser, 'sanctum')
            ->postJson("/api/listings/{$listing->id}/comments", [
                'content' => 'Offer: 50k cash',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Retrieve discussion comments on ads.
     */
    public function test_get_listing_comments(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Fitted Wardrobe',
            'description' => 'Wooden wardrobe.',
            'price' => 25000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        Comment::create([
            'listing_id' => $listing->id,
            'user_id' => $this->verifiedUser->id,
            'content' => 'Comment 1',
        ]);

        Comment::create([
            'listing_id' => $listing->id,
            'user_id' => $this->verifiedUser->id,
            'content' => 'Comment 2',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson("/api/listings/{$listing->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.content', 'Comment 1')
            ->assertJsonPath('1.content', 'Comment 2');
    }

    /**
     * Test listings query with empty filter parameters does not filter out results.
     */
    public function test_listings_query_with_empty_filters_does_not_filter_out_results(): void
    {
        // Create a pending listing for the verified user
        Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Pending Electronics Item',
            'description' => 'Mint condition.',
            'price' => 5000,
            'category' => 'Electronics',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'pending',
        ]);

        // Request with empty category and search parameters along with user_id
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson("/api/listings?category=&search=&user_id={$this->verifiedUser->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Pending Electronics Item');
    }

    /**
     * Unverified residents are blocked from flagging listings.
     */
    public function test_unverified_resident_cannot_flag_listing(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Vintage Chair',
            'description' => 'Solid oak chair.',
            'price' => 5000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->unverifiedUser, 'sanctum')
            ->postJson("/api/listings/{$listing->id}/flag", [
                'reason' => 'spam',
                'comment' => 'This is spam.',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Verified residents can flag a listing.
     */
    public function test_verified_resident_can_flag_listing(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Vintage Chair',
            'description' => 'Solid oak chair.',
            'price' => 5000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $reporter = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $reporter->id,
            'phase' => 'Phase 1',
            'block' => 'Block C',
            'house_number' => '300',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/listings/{$listing->id}/flag", [
                'reason' => 'inappropriate',
                'comment' => 'Illegal items.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('flags_count', 1)
            ->assertJsonPath('status', 'active');

        $this->assertDatabaseHas('listing_flags', [
            'listing_id' => $listing->id,
            'user_id' => $reporter->id,
            'reason' => 'inappropriate',
            'comment' => 'Illegal items.',
        ]);
    }

    /**
     * Users cannot flag their own listing.
     */
    public function test_user_cannot_flag_own_listing(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Vintage Chair',
            'description' => 'Solid oak chair.',
            'price' => 5000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/listings/{$listing->id}/flag", [
                'reason' => 'spam',
            ]);

        $response->assertStatus(400);
    }

    /**
     * Users cannot flag a listing twice.
     */
    public function test_user_cannot_flag_listing_twice(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Vintage Chair',
            'description' => 'Solid oak chair.',
            'price' => 5000,
            'category' => 'Furniture',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        $reporter = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $reporter->id,
            'phase' => 'Phase 1',
            'block' => 'Block C',
            'house_number' => '300',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        // First flag
        $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/listings/{$listing->id}/flag", ['reason' => 'spam']);

        // Second flag
        $response = $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/listings/{$listing->id}/flag", ['reason' => 'spam']);

        $response->assertStatus(400);
    }

    /**
     * Listing status changes to flagged when threshold is met.
     */
    public function test_listing_auto_flagged_on_reaching_threshold(): void
    {
        $listing = Listing::create([
            'user_id' => $this->verifiedUser->id,
            'title' => 'Prohibited Item',
            'description' => 'Illegal product.',
            'price' => 10000,
            'category' => 'Other',
            'contact_whatsapp' => '+923001234567',
            'images' => [],
            'status' => 'active',
        ]);

        // Create 5 verified reporters and flag the listing
        for ($i = 0; $i < 5; $i++) {
            $reporter = User::factory()->create();
            ResidentProfile::create([
                'user_id' => $reporter->id,
                'phase' => 'Phase 1',
                'block' => 'Block C',
                'house_number' => '30' . $i,
                'user_type' => 'owner',
                'is_verified' => true,
                'status' => 'approved',
            ]);

            $response = $this->actingAs($reporter, 'sanctum')
                ->postJson("/api/listings/{$listing->id}/flag", [
                    'reason' => 'inappropriate',
                ]);

            if ($i === 4) {
                $response->assertStatus(200)
                    ->assertJsonPath('status', 'flagged');
            } else {
                $response->assertStatus(200)
                    ->assertJsonPath('status', 'active');
            }
        }

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'status' => 'flagged',
            'flags_count' => 5,
        ]);

        $this->assertDatabaseHas('moderation_logs', [
            'action' => 'auto_flag_listing',
            'target_type' => get_class($listing),
            'target_id' => $listing->id,
        ]);
    }

    /**
     * Test that the contact_whatsapp phone number is formatted correctly when saving a listing.
     */
    public function test_classified_ad_phone_number_is_formatted_correctly(): void
    {
        $testCases = [
            '03222911199' => '+923222911199',
            '3222911199' => '+923222911199',
            '923222911199' => '+923222911199',
            '+923222911199' => '+923222911199',
            '00923222911199' => '+923222911199',
            ' +92 322-2911199 ' => '+923222911199',
            '+1234567890' => '+1234567890',
        ];

        foreach ($testCases as $input => $expected) {
            $response = $this->actingAs($this->verifiedUser, 'sanctum')
                ->postJson('/api/listings', [
                    'title' => 'Test Phone formatting ' . uniqid(),
                    'description' => 'Test description formatting.',
                    'price' => 100,
                    'category' => 'Electronics',
                    'contact_whatsapp' => (string)$input,
                    'images' => [],
                ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('listings', [
                'id' => $response->json('id'),
                'contact_whatsapp' => $expected,
            ]);
        }
    }
}
