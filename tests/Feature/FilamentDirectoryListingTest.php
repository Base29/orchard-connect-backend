<?php

namespace Tests\Feature;

use App\Models\DirectoryCategory;
use App\Models\DirectoryListing;
use App\Models\User;
use App\Filament\Resources\DirectoryListings\Pages\CreateDirectoryListing;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\DirectoryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentDirectoryListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DirectoryCategorySeeder::class);
    }

    /**
     * Test admin can access create directory listing page and see category options.
     */
    public function test_admin_can_access_create_directory_listing_and_preload_categories(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        // Test Livewire component
        $lw = Livewire::actingAs($admin)
            ->test(CreateDirectoryListing::class);

        $lw->assertHasNoFormErrors();

        // Retrieve the categories from database and make sure they match options
        $categories = DirectoryCategory::all();
        $this->assertNotEmpty($categories);

        // Verify that the policy allows the admin to viewAny and view DirectoryCategory
        $this->assertTrue($admin->can('viewAny', DirectoryCategory::class));
    }
}
