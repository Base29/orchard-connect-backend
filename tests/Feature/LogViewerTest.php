<?php

namespace Tests\Feature;

use App\Models\User;
use App\Filament\Pages\LogViewer;
use App\Filament\Pages\LogDetail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    protected string $logPath;
    protected ?string $originalLogContent = null;
    protected bool $logFileExisted = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->logPath = storage_path('logs/laravel.log');
        
        // Backup existing log file if it exists
        if (file_exists($this->logPath)) {
            $this->logFileExisted = true;
            $this->originalLogContent = file_get_contents($this->logPath);
        }

        // Ensure directories exist
        if (!is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0755, true);
        }

        // Write sample test logs
        $sampleLogs = implode("\n", [
            "[2026-06-01 08:00:00] local.INFO: App initialized",
            "[2026-06-01 09:00:00] local.WARNING: Low disk space {\"disk\":\"/\"}",
            "[2026-06-01 10:00:00] local.ERROR: Database connection failed",
            "#0 /var/www/html/app.php:12",
            "#1 {main}",
            "[2026-06-01 11:00:00] local.DEBUG: Debugging message",
        ]) . "\n";

        file_put_contents($this->logPath, $sampleLogs);
    }

    protected function tearDown(): void
    {
        // Restore original log file or clean up
        if ($this->logFileExisted && $this->originalLogContent !== null) {
            file_put_contents($this->logPath, $this->originalLogContent);
        } elseif (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }

    public function test_only_superadmin_can_access_log_viewer(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('superadmin');

        $communityAdmin = User::factory()->create(['status' => 'active']);
        $communityAdmin->assignRole('community-admin');

        // Superadmin should access successfully
        Livewire::actingAs($superAdmin)
            ->test(LogViewer::class)
            ->assertStatus(200);

        // Community admin should get 403 / forbidden
        $this->actingAs($communityAdmin)
            ->get(LogViewer::getUrl())
            ->assertStatus(403);
    }

    public function test_only_superadmin_can_access_log_details(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('superadmin');

        $communityAdmin = User::factory()->create(['status' => 'active']);
        $communityAdmin->assignRole('community-admin');

        // Superadmin should access successfully
        Livewire::actingAs($superAdmin)
            ->test(LogDetail::class, ['index' => 1])
            ->assertStatus(200);

        // Community admin should get 403 / forbidden
        $this->actingAs($communityAdmin)
            ->get(LogDetail::getUrl(['index' => 1]))
            ->assertStatus(403);
    }

    public function test_log_viewer_lists_parsed_logs(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('superadmin');

        Livewire::actingAs($superAdmin)
            ->test(LogViewer::class)
            ->assertSee('App initialized')
            ->assertSee('Low disk space')
            ->assertSee('Database connection failed')
            ->assertSee('Debugging message');
    }

    public function test_log_viewer_filters_by_search_and_level(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('superadmin');

        // Test search
        Livewire::actingAs($superAdmin)
            ->test(LogViewer::class)
            ->set('search', 'disk')
            ->assertSee('Low disk space')
            ->assertDontSee('App initialized')
            ->assertDontSee('Database connection failed');

        // Test level filter
        Livewire::actingAs($superAdmin)
            ->test(LogViewer::class)
            ->set('level', 'ERROR')
            ->assertSee('Database connection failed')
            ->assertDontSee('App initialized')
            ->assertDontSee('Low disk space');
    }

    public function test_superadmin_can_delete_individual_log_entry(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('superadmin');

        // We have 4 entries. Let's delete the ERROR log (index 2)
        // Entries:
        // Index 0: App initialized
        // Index 1: Low disk space
        // Index 2: Database connection failed (with stack trace)
        // Index 3: Debugging message

        Livewire::actingAs($superAdmin)
            ->test(LogViewer::class)
            ->call('deleteLog', 2)
            ->assertHasNoErrors();

        // Check if log is removed from view and file
        $content = file_get_contents($this->logPath);
        $this->assertStringNotContainsString('Database connection failed', $content);
        $this->assertStringContainsString('App initialized', $content);
        $this->assertStringContainsString('Low disk space', $content);
        $this->assertStringContainsString('Debugging message', $content);
    }

    public function test_superadmin_can_clear_all_logs(): void
    {
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->assignRole('superadmin');

        Livewire::actingAs($superAdmin)
            ->test(LogViewer::class)
            ->call('clearAll')
            ->assertHasNoErrors();

        // Check if log file is empty
        $this->assertEquals('', file_get_contents($this->logPath));
    }
}
