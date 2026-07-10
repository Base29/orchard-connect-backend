<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResidentProfile;
use App\Filament\Resources\Users\Pages\EditUser;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Seed additional users required by tests that are no longer created by RolesAndPermissionsSeeder
        if (!User::where('email', 'me@imfaisal.pro')->exists()) {
            User::factory()->create([
                'name' => 'Faisal Hussain',
                'email' => 'me@imfaisal.pro',
                'status' => 'active',
            ])->assignRole('superadmin');
        }

        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test Admin',
                'email' => 'test@example.com',
                'status' => 'active',
            ])->assignRole('superadmin');
        }

        if (!User::where('email', 'community_admin@orchard.com')->exists()) {
            User::factory()->create([
                'name' => 'Community Admin User',
                'email' => 'community_admin@orchard.com',
                'status' => 'active',
            ])->assignRole('community-admin');
        }
    }

    public function test_admin_can_edit_user_with_resident_profile(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        $user = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $user->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '12',
            'street_number' => 'Street 1',
            'user_type' => 'owner',
            'is_verified' => true,
        ]);

        $lw = Livewire::actingAs($admin)
            ->test(EditUser::class, [
                'record' => $user->id,
            ]);

        $lw->set('data.name', 'Updated Name')
            ->call('save');

        $lw->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_edit_user_without_resident_profile(): void
    {
        $admin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$admin) {
            $admin = User::factory()->create();
            $admin->assignRole('superadmin');
        }

        $user = User::factory()->create();
        // No resident profile created!

        $lw = Livewire::actingAs($admin)
            ->test(EditUser::class, [
                'record' => $user->id,
            ]);

        $lw->set('data.name', 'Updated Name')
            ->call('save');

        // Check if there are form errors
        if ($lw->errors()) {
            fwrite(STDERR, "Errors found:\n" . print_r($lw->errors(), true) . "\n");
        }

        $lw->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_community_admin_cannot_edit_or_delete_superadmin_user(): void
    {
        $communityAdmin = User::where('email', 'community_admin@orchard.com')->first();
        $superadmin = User::where('email', 'me@imfaisal.pro')->first();

        // 1. Assert policy blocks community-admin from updating superadmin
        $this->assertFalse($communityAdmin->can('update', $superadmin));

        // 2. Assert policy blocks community-admin from deleting superadmin
        $this->assertFalse($communityAdmin->can('delete', $superadmin));

        // 3. Assert EditUser page is not accessible for superadmin record by community-admin (throws ModelNotFoundException due to query scoping)
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::actingAs($communityAdmin)
            ->test(EditUser::class, [
                'record' => $superadmin->id,
            ]);
    }

    public function test_community_admin_can_edit_regular_user_and_assign_moderator_roles(): void
    {
        $communityAdmin = User::where('email', 'community_admin@orchard.com')->first();
        $user = User::factory()->create();

        // Assert policy allows community-admin to update regular user
        $this->assertTrue($communityAdmin->can('update', $user));

        // Assert policy blocks community-admin from deleting regular user
        $this->assertFalse($communityAdmin->can('delete', $user));

        // Get the ID of the content-moderator role
        $roleId = \Spatie\Permission\Models\Role::where('name', 'content-moderator')->value('id');

        // Use Livewire to edit and set roles to content-moderator
        $lw = Livewire::actingAs($communityAdmin)
            ->test(EditUser::class, [
                'record' => $user->id,
            ]);

        $lw->set('data.roles', [$roleId])
            ->call('save');

        $lw->assertHasNoFormErrors();

        // Verify the user has the role assigned
        $this->assertTrue($user->fresh()->hasRole('content-moderator'));
    }

    public function test_community_admin_can_ban_user_but_cannot_ban_superadmin(): void
    {
        $communityAdmin = User::where('email', 'community_admin@orchard.com')->first();
        $user = User::factory()->create(['status' => 'active']);
        $superadmin = User::where('email', 'me@imfaisal.pro')->first();

        $lw = Livewire::actingAs($communityAdmin)
            ->test(\App\Filament\Resources\Users\Pages\ListUsers::class);

        // Verify table action visibility and superadmin absence
        $lw->assertTableActionVisible('ban', $user);
        $lw->assertCanNotSeeTableRecords([$superadmin]);

        // Mount and run the ban action
        $lw->mountTableAction('ban', $user);
        
        $mountedActions = $lw->get('mountedActions');
        $mountedActions[0]['data']['reason'] = 'Spamming the feed.';
        $lw->set('mountedActions', $mountedActions);

        $lw->callMountedTableAction();

        $lw->assertHasNoTableActionErrors();

        $this->assertEquals('banned', $user->fresh()->status);
        $this->assertDatabaseHas('moderation_logs', [
            'action' => 'ban_user',
            'target_id' => $user->id,
            'moderator_id' => $communityAdmin->id,
            'reason' => 'Spamming the feed.',
        ]);
    }

    public function test_maintenance_mode_widget_visible_only_to_superadmin(): void
    {
        $superadmin = User::where('email', 'me@imfaisal.pro')->first();
        $communityAdmin = User::where('email', 'community_admin@orchard.com')->first();

        // Superadmin should view
        $this->actingAs($superadmin);
        $this->assertTrue(\App\Filament\Widgets\MaintenanceModeWidget::canView());

        // Community admin should not view
        $this->actingAs($communityAdmin);
        $this->assertFalse(\App\Filament\Widgets\MaintenanceModeWidget::canView());
    }

    public function test_superadmin_can_edit_and_delete_superadmin_user(): void
    {
        $superadmin1 = User::where('email', 'me@imfaisal.pro')->first();
        $superadmin2 = User::where('email', 'test@example.com')->first();

        // Assert policy allows superadmin to update/delete another superadmin
        $this->assertTrue($superadmin1->can('update', $superadmin2));
        $this->assertTrue($superadmin1->can('delete', $superadmin2));
    }

    public function test_superadmin_can_verify_user_email_via_table_action(): void
    {
        $superadmin = User::where('email', 'me@imfaisal.pro')->first();
        if (!$superadmin) {
            $superadmin = User::factory()->create();
            $superadmin->assignRole('superadmin');
        }

        $user = User::factory()->unverified()->create();

        $lw = Livewire::actingAs($superadmin)
            ->test(\App\Filament\Resources\Users\Pages\ListUsers::class);

        // Verify table action visibility
        $lw->assertTableActionVisible('verify_email', $user);

        // Call the action
        $lw->callTableAction('verify_email', $user);

        $lw->assertHasNoTableActionErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('moderation_logs', [
            'action' => 'verify_email',
            'target_id' => $user->id,
            'moderator_id' => $superadmin->id,
        ]);

        // Action should now be hidden since email is verified
        $lw->assertTableActionHidden('verify_email', $user);
    }
}
