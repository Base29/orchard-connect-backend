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

        // 1. Define Granular Permissions
        $permissions = [
            // User management
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'verify_user',
            'ban_user',

            // Post moderation
            'view_any_post',
            'view_post',
            'create_post',
            'update_post',
            'delete_post',
            'moderate_post',

            // Comment moderation
            'view_any_comment',
            'view_comment',
            'create_comment',
            'update_comment',
            'delete_comment',
            'moderate_comment',

            // Listings (marketplace ads)
            'view_any_listing',
            'view_listing',
            'create_listing',
            'update_listing',
            'delete_listing',
            'moderate_listing',

            // Directory Listings
            'view_any_directory_listing',
            'view_directory_listing',
            'create_directory_listing',
            'update_directory_listing',
            'delete_directory_listing',

            // Directory Categories
            'view_any_directory_category',
            'view_directory_category',
            'create_directory_category',
            'update_directory_category',
            'delete_directory_category',

            // Moderation logs (read-only auditing)
            'view_any_moderation_log',
            'view_moderation_log',

            // Security Roles
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',

            // Security Permissions
            'view_any_permission',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',

            // Announcements
            'view_any_announcement',
            'view_announcement',
            'create_announcement',
            'update_announcement',
            'delete_announcement',

            // News
            'view_any_news',
            'view_news',
            'create_news',
            'update_news',
            'delete_news',
        ];

        // Seed permissions in Spatie
        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        // Forget cached permissions to force a reload from DB before role syncing
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Create Roles and Sync Permissions
        
        // Feed Moderator
        $feedModRole = Role::findOrCreate('Feed Moderator', 'web');
        $feedModRole->syncPermissions([
            'view_any_user',
            'view_user',
            'view_any_post',
            'view_post',
            'create_post',
            'update_post',
            'delete_post',
            'moderate_post',
            'view_any_comment',
            'view_comment',
            'create_comment',
            'update_comment',
            'delete_comment',
            'moderate_comment',
            'view_any_moderation_log',
            'view_moderation_log',
            'view_any_announcement',
            'view_announcement',
            'create_announcement',
            'update_announcement',
            'delete_announcement',
            'view_any_news',
            'view_news',
            'create_news',
            'update_news',
            'delete_news',
        ]);

        // Marketplace Moderator
        $marketModRole = Role::findOrCreate('Marketplace Moderator', 'web');
        $marketModRole->syncPermissions([
            'view_any_user',
            'view_user',
            'view_any_listing',
            'view_listing',
            'create_listing',
            'update_listing',
            'delete_listing',
            'moderate_listing',
            'view_any_directory_listing',
            'view_directory_listing',
            'create_directory_listing',
            'update_directory_listing',
            'delete_directory_listing',
            'view_any_directory_category',
            'view_directory_category',
            'create_directory_category',
            'update_directory_category',
            'delete_directory_category',
            'view_any_moderation_log',
            'view_moderation_log',
        ]);

        // Super Admin
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions(Permission::all());

        // 3. Seed / Assign Roles to Administrator & Moderator Accounts
        
        // Super Admin: me@imfaisal.pro
        $faisal = User::where('email', 'me@imfaisal.pro')->first();
        if ($faisal) {
            $faisal->assignRole($superAdminRole);
            $this->command->info('Role "Super Admin" successfully assigned to existing user: me@imfaisal.pro');
        } else {
            $faisal = User::create([
                'name' => 'Faisal Hussain',
                'email' => 'me@imfaisal.pro',
                'password' => bcrypt('password123'),
                'status' => 'active',
            ]);
            $faisal->assignRole($superAdminRole);
            $this->command->info('Created Super Admin user: me@imfaisal.pro (password: password123)');
        }

        // Test fallback Admin: test@example.com
        $testAdmin = User::where('email', 'test@example.com')->first();
        if ($testAdmin) {
            $testAdmin->assignRole($superAdminRole);
            $this->command->info('Role "Super Admin" assigned to test@example.com');
        }

        // Feed Moderator test user
        $feedModUser = User::where('email', 'feed_moderator@orchard.com')->first();
        if (!$feedModUser) {
            $feedModUser = User::create([
                'name' => 'Feed Moderator User',
                'email' => 'feed_moderator@orchard.com',
                'password' => bcrypt('password123'),
                'status' => 'active',
            ]);
            $feedModUser->assignRole($feedModRole);
            $this->command->info('Created Feed Moderator: feed_moderator@orchard.com (password: password123)');
        } else {
            $feedModUser->assignRole($feedModRole);
        }

        // Marketplace Moderator test user
        $marketModUser = User::where('email', 'market_moderator@orchard.com')->first();
        if (!$marketModUser) {
            $marketModUser = User::create([
                'name' => 'Marketplace Moderator User',
                'email' => 'market_moderator@orchard.com',
                'password' => bcrypt('password123'),
                'status' => 'active',
            ]);
            $marketModUser->assignRole($marketModRole);
            $this->command->info('Created Marketplace Moderator: market_moderator@orchard.com (password: password123)');
        } else {
            $marketModUser->assignRole($marketModRole);
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
