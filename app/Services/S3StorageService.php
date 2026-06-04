<?php
 
namespace App\Services;
 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
 
class S3StorageService
{
    protected ?string $keyId;
    protected ?string $bucket;
 
    public function __construct()
    {
        $this->keyId = env('AWS_ACCESS_KEY_ID');
        $this->bucket = env('AWS_BUCKET');
    }
 
    /**
     * Upload a public image/file to AWS S3 (or public disk fallback).
     * Returns the public URL of the uploaded asset.
     */
    public function upload($file, string $path): string
    {
        if (empty($this->keyId) || empty($this->bucket)) {
            Log::info('S3 Storage: Credentials missing. Falling back to local public disk.');
            
            $localPath = Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));
            return asset('storage/' . $localPath);
        }
 
        try {
            // Upload to S3 with public visibility
            Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), [
                'visibility' => 'public',
            ]);
 
            return Storage::disk('s3')->url($path);
        } catch (\Throwable $e) {
            Log::error('S3 Storage Upload Exception: ' . $e->getMessage());
 
            $localPath = Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));
            return asset('storage/' . $localPath);
        }
    }
}
