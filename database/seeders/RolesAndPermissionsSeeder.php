<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Pruned Granular Permissions
        $permissions = [
            // Superadmin Tier
            'manage-system',
            'create-roles',
            'create-permissions',
            'assign-admin-roles',
            'view-audit-logs',

            // Community Admin Tier
            'assign-moderator-roles',
            'verify-residents',
            'moderate-polls',
            'verify-businesses',
            'override-moderation',

            // Marketplace Moderator Tier
            'review-listings',
            'archive-listings',

            // Content Moderator Tier
            'create-news',
            'moderate-comments',
            'manage-polls',

            // Support Tickets Tier
            'manage-support-tickets',
        ];

        // Clean up legacy/redundant permissions and roles from the database
        $allowedRoles = ['superadmin', 'community-admin', 'marketplace-moderator', 'content-moderator', 'support-staff'];
        Permission::whereNotIn('name', $permissions)->delete();
        Role::whereNotIn('name', $allowedRoles)->delete();

        // Seed permissions in Spatie
        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        // Forget cached permissions to force a reload from DB before role syncing
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Create Roles and Sync Permissions
        
        // Superadmin
        $superAdminRole = Role::findOrCreate('superadmin', 'web');
        $superAdminRole->syncPermissions($permissions);

        // Community Admin
        $communityAdminRole = Role::findOrCreate('community-admin', 'web');
        $communityAdminRole->syncPermissions([
            'assign-moderator-roles',
            'verify-residents',
            'moderate-polls',
            'verify-businesses',
            'override-moderation',
        ]);

        // Marketplace Moderator
        $marketModRole = Role::findOrCreate('marketplace-moderator', 'web');
        $marketModRole->syncPermissions([
            'review-listings',
            'archive-listings',
        ]);

        // Content Moderator
        $contentModRole = Role::findOrCreate('content-moderator', 'web');
        $contentModRole->syncPermissions([
            'create-news',
            'moderate-comments',
            'manage-polls',
        ]);

        // Support Staff
        $supportStaffRole = Role::findOrCreate('support-staff', 'web');
        $supportStaffRole->syncPermissions([
            'manage-support-tickets',
        ]);

        // 3. Seed / Assign Roles to Administrator Accounts
        
        // Superadmin: me@imfaisal.pro
        $faisal = User::where('email', 'me@imfaisal.pro')->first();
        if ($faisal) {
            $faisal->update(['password' => bcrypt('password123')]);
            $faisal->assignRole($superAdminRole);
            $this->command->info('Role "superadmin" successfully assigned and password reset to password123 for existing user: me@imfaisal.pro');
        } else {
            $faisal = User::create([
                'name' => 'Faisal Hussain',
                'email' => 'me@imfaisal.pro',
                'password' => bcrypt('password123'),
                'status' => 'active',
            ]);
            $faisal->assignRole($superAdminRole);
            $this->command->info('Created superadmin user: me@imfaisal.pro (password: password123)');
        }

        // 4. Seed Default Announcements
        $adminUser = User::where('email', 'me@imfaisal.pro')->first();
        $adminId = $adminUser ? $adminUser->id : null;

        \App\Models\Announcement::updateOrCreate(
            ['title' => 'Scheduled Power Outage'],
            [
                'content' => 'Phase 1 grid inspection on June 5th, 10:00 AM - 2:00 PM.',
                'category' => 'maintenance',
                'status' => 'published',
                'pinned' => 'true',
                'author_id' => $adminId,
            ]
        );

        \App\Models\Announcement::updateOrCreate(
            ['title' => 'Monthly General Assembly'],
            [
                'content' => 'Meeting regarding security system enhancements at central office, June 8th.',
                'category' => 'event',
                'status' => 'published',
                'pinned' => 'false',
                'author_id' => $adminId,
            ]
        );

        $this->command->info('Default Announcements seeded successfully!');
    }
}
