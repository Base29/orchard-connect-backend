<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResidentProfile;
use App\Models\DirectoryCategory;
use App\Models\DirectoryListing;
use App\Models\DirectoryReview;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessReviewApiTest extends TestCase
{
    use RefreshDatabase;

    private User $verifiedUser;
    private User $unverifiedUser;
    private DirectoryCategory $category;
    private DirectoryListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);

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

        // Create a Category
        $this->category = DirectoryCategory::create([
            'name' => 'Restaurants',
            'slug' => 'restaurants',
            'icon' => 'utensils',
        ]);

        // Create a Listing
        $this->listing = DirectoryListing::create([
            'category_id' => $this->category->id,
            'name' => 'The Orchard Grill',
            'description' => 'Fine dining restaurant in Bahria Town.',
            'address' => 'Commercial Area, Phase 1',
            'contact_phone' => '+923001234567',
            'whatsapp' => '+923001234567',
            'logo_url' => 'https://example.com/logo.png',
            'is_verified' => true,
        ]);
    }

    /**
     * Test directory listings index retrieves reviews count and average rating.
     */
    public function test_get_directory_listings_with_ratings(): void
    {
        // Create multiple reviews
        DirectoryReview::create([
            'user_id' => $this->verifiedUser->id,
            'directory_listing_id' => $this->listing->id,
            'rating' => 5,
            'comment' => 'Excellent service and food!',
        ]);

        $secondUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $secondUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '101',
            'user_type' => 'tenant',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        DirectoryReview::create([
            'user_id' => $secondUser->id,
            'directory_listing_id' => $this->listing->id,
            'rating' => 4,
            'comment' => 'Good, but a bit slow.',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson('/api/directory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'listings' => [
                        '*' => [
                            'id',
                            'name',
                            'reviews_count',
                            'reviews_avg_rating',
                        ]
                    ]
                ]
            ]);

        // Average of 5 and 4 is 4.5
        $this->assertEquals(2, $response->json('0.listings.0.reviews_count'));
        $this->assertEquals(4.5, (float)$response->json('0.listings.0.reviews_avg_rating'));
    }

    /**
     * Test single listing details with loaded reviews.
     */
    public function test_get_single_directory_listing_details_with_reviews(): void
    {
        DirectoryReview::create([
            'user_id' => $this->verifiedUser->id,
            'directory_listing_id' => $this->listing->id,
            'rating' => 5,
            'comment' => 'Excellent service and food!',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->getJson("/api/directory/{$this->listing->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $this->listing->id);
            
        $this->assertEquals(1, $response->json('reviews_count'));
        $this->assertEquals(5.0, (float)$response->json('reviews_avg_rating'));
        $response->assertJsonStructure([
                'reviews' => [
                    '*' => [
                        'id',
                        'rating',
                        'comment',
                        'user' => [
                            'id',
                            'name',
                            'resident_profile',
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Unverified residents are blocked from posting reviews.
     */
    public function test_unverified_resident_cannot_create_review(): void
    {
        $response = $this->actingAs($this->unverifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'rating' => 4,
                'comment' => 'Nice place.',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Verified residents can post a review.
     */
    public function test_verified_resident_can_create_review(): void
    {
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'rating' => 5,
                'comment' => 'Great experience!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('comment', 'Great experience!')
            ->assertJsonStructure([
                'id',
                'user' => [
                    'id',
                    'name',
                    'resident_profile',
                ]
            ]);

        $this->assertDatabaseHas('directory_reviews', [
            'directory_listing_id' => $this->listing->id,
            'user_id' => $this->verifiedUser->id,
            'rating' => 5,
            'comment' => 'Great experience!',
        ]);
    }

    /**
     * Verified residents can update their review in place (no duplicate records).
     */
    public function test_verified_resident_can_update_review_in_place(): void
    {
        // First review
        $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'rating' => 4,
                'comment' => 'Decent.',
            ]);

        // Update the review
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'rating' => 5,
                'comment' => 'Actually, it is fantastic!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('comment', 'Actually, it is fantastic!');

        // Confirm only one review exists
        $this->assertDatabaseCount('directory_reviews', 1);
        $this->assertDatabaseHas('directory_reviews', [
            'directory_listing_id' => $this->listing->id,
            'user_id' => $this->verifiedUser->id,
            'rating' => 5,
            'comment' => 'Actually, it is fantastic!',
        ]);
    }

    /**
     * Validate fields on review submission.
     */
    public function test_validation_of_review_creation(): void
    {
        // Missing rating
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'comment' => 'No rating here.',
            ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['rating']);

        // Rating too low
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'rating' => 0,
            ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['rating']);

        // Rating too high
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'rating' => 6,
            ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['rating']);

        // Comment too long
        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->postJson("/api/directory/{$this->listing->id}/reviews", [
                'rating' => 5,
                'comment' => str_repeat('A', 1001),
            ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['comment']);
    }

    /**
     * User can delete their own review.
     */
    public function test_owner_can_delete_their_review(): void
    {
        $review = DirectoryReview::create([
            'user_id' => $this->verifiedUser->id,
            'directory_listing_id' => $this->listing->id,
            'rating' => 4,
            'comment' => 'Nice place.',
        ]);

        $response = $this->actingAs($this->verifiedUser, 'sanctum')
            ->deleteJson("/api/directory/reviews/{$review->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('directory_reviews', ['id' => $review->id]);
    }

    /**
     * Non-owner cannot delete another user's review.
     */
    public function test_non_owner_cannot_delete_review(): void
    {
        $review = DirectoryReview::create([
            'user_id' => $this->verifiedUser->id,
            'directory_listing_id' => $this->listing->id,
            'rating' => 4,
            'comment' => 'Nice place.',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/directory/reviews/{$review->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('directory_reviews', ['id' => $review->id]);
    }

    /**
     * Admin can delete any review.
     */
    public function test_admin_can_delete_any_review(): void
    {
        $review = DirectoryReview::create([
            'user_id' => $this->verifiedUser->id,
            'directory_listing_id' => $this->listing->id,
            'rating' => 4,
            'comment' => 'Nice place.',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/directory/reviews/{$review->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('directory_reviews', ['id' => $review->id]);
    }
}
