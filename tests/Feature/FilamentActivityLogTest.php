<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Filament\Resources\Activities\Pages\ManageActivities;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class FilamentActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Guest cannot access activity logs.
     */
    public function test_guests_cannot_access_activity_logs(): void
    {
        $this->get('/admin/activities')->assertRedirect('/admin/login');
    }

    /**
     * Moderators are forbidden from accessing activity logs.
     */
    public function test_moderators_cannot_access_activity_logs(): void
    {
        $moderator = User::factory()->create(['status' => 'active']);
        $moderator->assignRole('content-moderator');

        $this->actingAs($moderator)
            ->get('/admin/activities')
            ->assertStatus(403);
    }

    /**
     * Super Admin can access activity logs.
     */
    public function test_super_admin_can_access_activity_logs(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('superadmin');

        $this->actingAs($admin)
            ->get('/admin/activities')
            ->assertStatus(200);
    }

    /**
     * Model activities are recorded and displayed in the table.
     */
    public function test_activity_logs_are_recorded_and_listed(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('superadmin');

        // Trigger created event by creating a post
        $post = Post::create([
            'user_id' => $admin->id,
            'content' => 'This is a test post content for activity logging',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'event' => 'created',
        ]);

        // Verify that Livewire table lists the activities
        Livewire::actingAs($admin)
            ->test(ManageActivities::class)
            ->assertCanSeeTableRecords(Activity::all());
    }
}
