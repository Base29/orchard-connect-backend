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
}
