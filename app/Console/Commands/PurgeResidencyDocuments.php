<?php

namespace App\Console\Commands;

use App\Models\ResidentProfile;
use App\Services\S3PrivateStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeResidencyDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-residency-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge verification documents for approved and rejected residency verification requests from S3 (or local fallback) storage';

    /**
     * Execute the console command.
     */
    public function handle(S3PrivateStorageService $storage)
    {
        $this->info('Starting residency verification documents purge...');

        $bucket = config('filesystems.disks.s3.bucket');
        if (!empty($bucket)) {
            $this->info("Target S3 Bucket: {$bucket}");
        } else {
            $this->info("Target S3 Bucket: Not configured");
        }

        $profiles = ResidentProfile::whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('document_path')
            ->where('document_path', '!=', 'purged')
            ->orderBy('user_id')
            ->get();

        $count = 0;

        foreach ($profiles as $profile) {
            $path = $profile->document_path;
            
            $this->comment("Purging document for user {$profile->user_id} (Status: {$profile->status}): {$path}");

            $isLocal = str_starts_with($path, 'local://');
            $isS3 = str_starts_with($path, 's3://');

            $exists = false;
            $canCheck = true;

            if ($isLocal) {
                $localPath = str_replace('local://', '', $path);
                $exists = Storage::disk('local')->exists($localPath);
            } elseif ($isS3) {
                $s3Path = str_replace('s3://', '', $path);
                $keyId = config('filesystems.disks.s3.key');
                $bucket = config('filesystems.disks.s3.bucket');
                if (!empty($keyId) && !empty($bucket)) {
                    try {
                        $exists = Storage::disk('s3')->exists($s3Path);
                    } catch (\Throwable $e) {
                        $this->error("Failed to check existence on S3 for path: {$path}. Error: " . $e->getMessage());
                        Log::error("Purge command failed to check existence on S3 for path: {$path}. Error: " . $e->getMessage());
                        $canCheck = false;
                    }
                } else {
                    $this->error("S3 credentials missing. Cannot verify S3 document at: {$path}");
                    Log::error("Purge command cannot verify/delete S3 document at: {$path} because S3 credentials are not configured.");
                    $canCheck = false;
                }
            } else {
                // Legacy path or something without prefix, treat as S3
                $keyId = config('filesystems.disks.s3.key');
                $bucket = config('filesystems.disks.s3.bucket');
                if (!empty($keyId) && !empty($bucket)) {
                    try {
                        $exists = Storage::disk('s3')->exists($path);
                    } catch (\Throwable $e) {
                        $this->error("Failed to check existence on S3 for legacy path: {$path}. Error: " . $e->getMessage());
                        $canCheck = false;
                    }
                } else {
                    $this->error("S3 credentials missing. Cannot verify legacy S3 document at: {$path}");
                    $canCheck = false;
                }
            }

            // If we couldn't check (e.g. S3 config missing), do not update the DB.
            if (!$canCheck) {
                continue;
            }

            if (!$exists) {
                $this->info("Document already missing from storage, marking as purged in DB.");
                $profile->update([
                    'document_path' => 'purged',
                ]);
                $count++;
                continue;
            }

            // File exists, attempt deletion
            if ($isS3 || (!$isLocal && !str_starts_with($path, 'local://'))) {
                $bucket = config('filesystems.disks.s3.bucket');
                $this->comment("Deleting from S3 bucket '{$bucket}': {$path}");
            }
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

        $this->info("Scanning storage for orphaned/unpurged residency documents...");

        // 1. Scan Local Storage
        try {
            $localFiles = Storage::disk('local')->allFiles('documents');
            foreach ($localFiles as $file) {
                $userId = $this->extractUserId($file);
                if ($userId) {
                    $profile = ResidentProfile::where('user_id', $userId)->first();
                    if (!$profile || in_array($profile->status, ['approved', 'rejected'])) {
                        $this->comment("Purging orphaned local document for user {$userId}: local://{$file}");
                        if (Storage::disk('local')->delete($file)) {
                            if ($profile && $profile->document_path !== 'purged') {
                                $profile->update(['document_path' => 'purged']);
                            }
                            $count++;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->error("Failed to list/delete local files. Error: " . $e->getMessage());
        }

        // 2. Scan S3 Storage
        $keyId = config('filesystems.disks.s3.key');
        $bucket = config('filesystems.disks.s3.bucket');
        if (!empty($keyId) && !empty($bucket)) {
            try {
                $s3Files = Storage::disk('s3')->allFiles('documents');
                foreach ($s3Files as $file) {
                    $userId = $this->extractUserId($file);
                    if ($userId) {
                        $profile = ResidentProfile::where('user_id', $userId)->first();
                        if (!$profile || in_array($profile->status, ['approved', 'rejected'])) {
                            $this->comment("Purging S3 document from bucket '{$bucket}' for user {$userId}: s3://{$file}");
                            if (Storage::disk('s3')->delete($file)) {
                                if ($profile && $profile->document_path !== 'purged') {
                                    $profile->update(['document_path' => 'purged']);
                                }
                                $count++;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Failed to list/delete S3 files. Error: " . $e->getMessage());
                Log::error("Purge command failed to scan/delete S3 documents. Error: " . $e->getMessage());
            }
        }

        $this->info("Purged {$count} residency verification documents successfully.");
        Log::info("Residency verification documents purge run completed. Purged count: {$count}.");

        return Command::SUCCESS;
    }

    /**
     * Extract user UUID from file path.
     */
    private function extractUserId(string $path): ?string
    {
        $parts = explode('/', str_replace('\\', '/', $path));
        // Check if it matches documents/{user_id}/{filename} or documents/demo/{user_id}/{filename}
        if (count($parts) >= 3 && $parts[0] === 'documents') {
            $potentialUserId = $parts[1] === 'demo' ? ($parts[2] ?? null) : $parts[1];
            if ($potentialUserId && \Illuminate\Support\Str::isUuid($potentialUserId)) {
                return $potentialUserId;
            }
        }
        return null;
    }
}
