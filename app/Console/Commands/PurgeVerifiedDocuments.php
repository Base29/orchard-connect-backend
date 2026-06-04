<?php
 
namespace App\Console\Commands;
 
use App\Models\ResidentProfile;
use App\Services\SupabaseStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
 
class PurgeVerifiedDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-verified-documents';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge verification documents for already verified residents from Supabase (or local fallback) storage after approval';
 
    /**
     * Execute the console command.
     */
    public function handle(SupabaseStorageService $storage)
    {
        $this->info('Starting verification document purge...');
 
        $isVerified = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'true' : true;
        $profiles = ResidentProfile::where('status', 'approved')
            ->where('is_verified', $isVerified)
            ->whereNotNull('document_path')
            ->where('document_path', '!=', 'purged')
            ->get();
 
        $count = 0;
 
        foreach ($profiles as $profile) {
            $path = $profile->document_path;
            
            $this->comment("Purging document for user {$profile->user_id}: {$path}");
 
            $deleted = $storage->delete($path);
 
            if ($deleted) {
                $profile->update([
                    'document_path' => 'purged',
                ]);
                $count++;
            } else {
                $this->error("Failed to delete document at path: {$path}");
                Log::error("Purge command failed to delete document at path: {$path} for resident profile: {$profile->id}");
            }
        }
 
        $this->info("Purged {$count} verification documents successfully.");
        Log::info("Verification document purge run completed. Purged count: {$count}.");
 
        return Command::SUCCESS;
    }
}
