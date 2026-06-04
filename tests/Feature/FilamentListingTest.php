<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Filament\Resources\Listings\Pages\EditListing;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Test admin can update listing details through the Filament form.
     */
    public function test_admin_can_edit_listing_in_filament(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('Super Admin');
        }

        // Create a listing to edit
        $seller = User::factory()->create();
        $listing = Listing::create([
            'user_id' => $seller->id,
            'title' => 'iPhone 14 Pro Max',
            'description' => 'test description',
            'price' => 300000.00,
            'category' => 'Electronics',
            'images' => [],
            'contact_whatsapp' => '+923222911199',
            'status' => 'active',
        ]);

        \Illuminate\Support\Facades\Event::fake([\App\Events\ListingStatusUpdated::class]);

        // Test Livewire component
        $lw = Livewire::actingAs($admin)
            ->test(EditListing::class, [
                'record' => $listing->id,
            ]);
            
        $lw->set('data.title', 'iPhone 14 Pro Max (Updated)')
            ->set('data.price', 290000.00)
            ->set('data.status', 'suspended')
            ->call('save');

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\ListingStatusUpdated::class, function ($event) use ($listing) {
            return $event->listingId === $listing->id && $event->status === 'suspended';
        });
            
        // Dump errors if any, or general page state
        if ($lw->errors()) {
            fwrite(STDERR, print_r($lw->errors(), true));
        }

        $lw->assertHasNoFormErrors();

        // Verify database state has updated
        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'title' => 'iPhone 14 Pro Max (Updated)',
            'price' => 290000.00,
            'status' => 'suspended',
        ]);
    }
}
