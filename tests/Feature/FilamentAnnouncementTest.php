<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Test admin can pin and unpin announcements through Filament.
     */
    public function test_admin_can_pin_and_unpin_announcements_in_filament(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        // Create an announcement to edit
        $announcement = Announcement::create([
            'title' => 'Important Maintenance Work',
            'content' => 'Water supply will be suspended on Sunday.',
            'author_id' => $admin->id,
            'category' => 'maintenance',
            'status' => 'published',
            'pinned' => true,
        ]);

        // 1. Test unpinning the announcement
        $lw = Livewire::actingAs($admin)
            ->test(EditAnnouncement::class, [
                'record' => $announcement->id,
            ]);

        $lw->set('data.pinned', false)
            ->call('save');

        if ($lw->errors()) {
            fwrite(STDERR, print_r($lw->errors(), true));
        }

        $lw->assertHasNoFormErrors();

        // Verify database state has updated to pinned => false
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'pinned' => false,
        ]);

        // 2. Test pinning it back
        $lw = Livewire::actingAs($admin)
            ->test(EditAnnouncement::class, [
                'record' => $announcement->id,
            ]);

        $lw->set('data.pinned', true)
            ->call('save');

        $lw->assertHasNoFormErrors();

        // Verify database state has updated to pinned => true
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'pinned' => true,
        ]);
    }
}
