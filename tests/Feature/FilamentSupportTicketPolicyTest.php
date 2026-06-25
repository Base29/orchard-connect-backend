<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentSupportTicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_community_admin_without_permission_cannot_view_any_support_tickets(): void
    {
        $communityAdmin = User::factory()->create(['status' => 'active']);
        $communityAdmin->assignRole('community-admin');

        // Verify they do not have the permission
        $this->assertFalse($communityAdmin->can('manage-support-tickets'));

        // Verify policy viewAny rejects them
        $this->assertFalse($communityAdmin->can('viewAny', SupportTicket::class));
    }

    public function test_community_admin_with_permission_can_view_any_support_tickets(): void
    {
        $communityAdmin = User::factory()->create(['status' => 'active']);
        $communityAdmin->assignRole('community-admin');
        $communityAdmin->givePermissionTo('manage-support-tickets');

        // Verify they have the permission
        $this->assertTrue($communityAdmin->can('manage-support-tickets'));

        // Verify policy viewAny accepts them
        $this->assertTrue($communityAdmin->can('viewAny', SupportTicket::class));
    }

    public function test_community_admin_without_permission_cannot_view_security_tickets(): void
    {
        $communityAdmin = User::factory()->create(['status' => 'active']);
        $communityAdmin->assignRole('community-admin');

        $ticket = SupportTicket::create([
            'tracking_id' => 'OC-TICK-SEC1',
            'category' => 'security',
            'subject' => 'Security Threat',
            'description' => 'Suspicious behavior detected.',
            'status' => 'pending',
        ]);

        // Verify they cannot view this ticket
        $this->assertFalse($communityAdmin->can('view', $ticket));
        $this->assertFalse($communityAdmin->can('update', $ticket));
    }

    public function test_community_admin_with_permission_can_view_security_tickets(): void
    {
        $communityAdmin = User::factory()->create(['status' => 'active']);
        $communityAdmin->assignRole('community-admin');
        $communityAdmin->givePermissionTo('manage-support-tickets');

        $ticket = SupportTicket::create([
            'tracking_id' => 'OC-TICK-SEC2',
            'category' => 'security',
            'subject' => 'Security Threat',
            'description' => 'Suspicious behavior detected.',
            'status' => 'pending',
        ]);

        // Verify they can view/update this ticket
        $this->assertTrue($communityAdmin->can('view', $ticket));
        $this->assertTrue($communityAdmin->can('update', $ticket));
    }
}
