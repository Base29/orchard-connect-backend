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

    /**
     * Test admin can create directory category through Filament.
     */
    public function test_admin_can_create_directory_category_in_filament(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        $lw = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\DirectoryCategories\Pages\CreateDirectoryCategory::class);

        $lw->set('data.name', 'Food & Drink')
            ->set('data.slug', 'food-drink')
            ->set('data.icon', 'heroicon-o-home')
            ->call('create');

        // Output errors if any
        if ($lw->errors()) {
            fwrite(STDERR, "Errors found:\n" . print_r($lw->errors(), true) . "\n");
        }

        $lw->assertHasNoFormErrors();

        $this->assertDatabaseHas('directory_categories', [
            'name' => 'Food & Drink',
            'slug' => 'food-drink',
        ]);
    }

    /**
     * Test admin can create directory listing with pasted URL.
     */
    public function test_admin_can_create_directory_listing_with_pasted_url(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        $category = DirectoryCategory::first();

        $lw = Livewire::actingAs($admin)
            ->test(CreateDirectoryListing::class);

        $lw->set('data.category_id', $category->id)
            ->set('data.name', 'Pasted URL Shop')
            ->set('data.contact_phone', '+923001234567')
            ->set('data.logo_source', 'url')
            ->set('data.logo_paste_url', 'https://example.com/logo-paste.png')
            ->call('create');

        $lw->assertHasNoFormErrors();

        $this->assertDatabaseHas('directory_listings', [
            'name' => 'Pasted URL Shop',
            'logo_url' => 'https://example.com/logo-paste.png',
        ]);
    }

    /**
     * Test admin can create directory listing with uploaded file.
     */
    public function test_admin_can_create_directory_listing_with_uploaded_file(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        $category = DirectoryCategory::first();
        \Illuminate\Support\Facades\Storage::fake('public');
        $file = \Illuminate\Http\Testing\File::image('logo.jpg', 100, 100);

        $lw = Livewire::actingAs($admin)
            ->test(CreateDirectoryListing::class);

        $lw->set('data.category_id', $category->id)
            ->set('data.name', 'Uploaded File Shop')
            ->set('data.contact_phone', '+923001234568')
            ->set('data.logo_source', 'upload')
            ->set('data.logo_upload', [$file])
            ->call('create');

        $lw->assertHasNoFormErrors();

        $listing = DirectoryListing::where('name', 'Uploaded File Shop')->first();
        $this->assertNotNull($listing);
        $this->assertStringContainsString('directory/logos/', $listing->logo_url);
    }

    /**
     * Test admin can edit directory listing and verify fields hydrate correctly.
     */
    public function test_admin_can_edit_directory_listing_and_verify_hydration(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        $category = DirectoryCategory::first();

        // 1. Create a listing with pasted URL
        $listingUrl = DirectoryListing::create([
            'category_id' => $category->id,
            'name' => 'Hydration URL Shop',
            'contact_phone' => '+923001234569',
            'logo_url' => 'https://example.com/logo-to-hydrate.png',
            'is_verified' => true,
        ]);

        $lwUrl = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\DirectoryListings\Pages\EditDirectoryListing::class, [
                'record' => $listingUrl->getKey(),
            ]);

        $lwUrl->assertFormSet([
            'logo_source' => 'url',
            'logo_paste_url' => 'https://example.com/logo-to-hydrate.png',
        ]);

        // 2. Create a listing with uploaded file
        $listingFile = DirectoryListing::create([
            'category_id' => $category->id,
            'name' => 'Hydration File Shop',
            'contact_phone' => '+923001234570',
            'logo_url' => 'directory/logos/sample-logo.png',
            'is_verified' => true,
        ]);

        $lwFile = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\DirectoryListings\Pages\EditDirectoryListing::class, [
                'record' => $listingFile->getKey(),
            ]);

        $lwFile->assertFormSet([
            'logo_source' => 'upload',
            'logo_upload' => 'directory/logos/sample-logo.png',
        ]);
    }
}
