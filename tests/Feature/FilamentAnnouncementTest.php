<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\News;
use App\Models\User;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\News\Pages\EditNews;
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

    /**
     * Test community admin can manage announcements.
     */
    public function test_community_admin_can_manage_announcements_in_filament(): void
    {
        $communityAdmin = User::factory()->create();
        $communityAdmin->assignRole('community-admin');

        // Create an announcement to edit
        $announcement = Announcement::create([
            'title' => 'Community Update',
            'content' => 'This is a community announcement.',
            'author_id' => $communityAdmin->id,
            'category' => 'general',
            'status' => 'published',
            'pinned' => false,
        ]);

        // Test editing the announcement as community admin
        $lw = Livewire::actingAs($communityAdmin)
            ->test(EditAnnouncement::class, [
                'record' => $announcement->id,
            ]);

        $lw->set('data.title', 'Updated Community Title')
            ->call('save');

        $lw->assertHasNoFormErrors();

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Updated Community Title',
        ]);
    }

    /**
     * Test community admin can manage news articles.
     */
    public function test_community_admin_can_manage_news_in_filament(): void
    {
        $communityAdmin = User::factory()->create();
        $communityAdmin->assignRole('community-admin');

        // Create a news article to edit
        $news = News::create([
            'title' => 'Community News Headline',
            'content' => 'This is a community news content.',
            'author_id' => $communityAdmin->id,
            'status' => 'published',
        ]);

        // Test editing the news article as community admin
        $lw = Livewire::actingAs($communityAdmin)
            ->test(EditNews::class, [
                'record' => $news->id,
            ]);

        $lw->set('data.title', 'Updated News Title')
            ->call('save');

        $lw->assertHasNoFormErrors();

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => 'Updated News Title',
        ]);
    }

    /**
     * Test community admin can create news articles.
     */
    public function test_community_admin_can_create_news_in_filament(): void
    {
        $communityAdmin = User::factory()->create();
        $communityAdmin->assignRole('community-admin');

        $lw = Livewire::actingAs($communityAdmin)
            ->test(\App\Filament\Resources\News\Pages\CreateNews::class);

        $lw->set('data.title', 'New Community News')
            ->set('data.content', 'This is content for community news.')
            ->set('data.status', 'published')
            ->call('create');

        $lw->assertHasNoFormErrors();

        $this->assertDatabaseHas('news', [
            'title' => 'New Community News',
            'content' => '<p>This is content for community news.</p>',
            'author_id' => $communityAdmin->id,
        ]);
    }
}
