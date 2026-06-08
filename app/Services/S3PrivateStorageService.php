<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class S3PrivateStorageService
{
    protected ?string $keyId;
    protected ?string $bucket;

    public function __construct()
    {
        $this->keyId = config('filesystems.disks.s3.key');
        $this->bucket = config('filesystems.disks.s3.bucket');
    }

    /**
     * Upload verification document to private storage.
     */
    public function upload($file, string $path): string
    {
        // Fallback to local private storage if S3 credentials are missing
        if (empty($this->keyId) || empty($this->bucket)) {
            Log::info('S3 Private Storage: Credentials missing. Falling back to local storage.');
            
            $localPath = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
            return 'local://' . $localPath;
        }

        try {
            // Upload to S3 with private visibility
            Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), [
                'visibility' => 'private',
            ]);

            return 's3://' . $path;
        } catch (\Throwable $e) {
            Log::error('S3 Private Storage Upload Exception: ' . $e->getMessage());
            
            // Fallback on exception
            $localPath = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
            return 'local://' . $localPath;
        }
    }

    /**
     * Delete verification document from storage.
     */
    public function delete(string $path): bool
    {
        if (str_starts_with($path, 'local://')) {
            $localPath = str_replace('local://', '', $path);
            if (Storage::disk('local')->exists($localPath)) {
                return Storage::disk('local')->delete($localPath);
            }
            return false;
        }

        if (str_starts_with($path, 's3://')) {
            $s3Path = str_replace('s3://', '', $path);

            if (empty($this->keyId) || empty($this->bucket)) {
                return false;
            }

            try {
                if (Storage::disk('s3')->exists($s3Path)) {
                    return Storage::disk('s3')->delete($s3Path);
                }
                return false;
            } catch (\Throwable $e) {
                Log::error('S3 Private Storage Delete Exception: ' . $e->getMessage());
                return false;
            }
        }

        // For backwards compatibility or legacy records without scheme prefix:
        if (empty($this->keyId) || empty($this->bucket)) {
            return false;
        }
        try {
            if (Storage::disk('s3')->exists($path)) {
                return Storage::disk('s3')->delete($path);
            }
        } catch (\Throwable $e) {
            Log::error('S3 Private Storage Delete Fallback Exception: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Fetch verification document content and mimetype.
     */
    public function get(string $path): array
    {
        // Handle local private files
        if (str_starts_with($path, 'local://')) {
            $localPath = str_replace('local://', '', $path);
            if (Storage::disk('local')->exists($localPath)) {
                return [
                    'content' => Storage::disk('local')->get($localPath),
                    'mime' => Storage::disk('local')->mimeType($localPath),
                ];
            }
            abort(404, 'Verification document not found locally');
        }

        // Handle S3 files (with or without s3:// prefix)
        $s3Path = str_starts_with($path, 's3://') ? str_replace('s3://', '', $path) : $path;

        if (empty($this->keyId) || empty($this->bucket)) {
            abort(404, 'S3 credentials missing and file is not local.');
        }

        try {
            if (Storage::disk('s3')->exists($s3Path)) {
                return [
                    'content' => Storage::disk('s3')->get($s3Path),
                    'mime' => Storage::disk('s3')->mimeType($s3Path),
                ];
            }
            abort(404, 'Verification document not found in remote storage');
        } catch (\Throwable $e) {
            Log::error('S3 Private Storage Fetch Exception: ' . $e->getMessage());
            abort(500, 'Error downloading document from remote storage');
        }
    }
}
