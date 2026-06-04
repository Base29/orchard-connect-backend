<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    protected ?string $url;
    protected ?string $key;
    protected string $bucket;

    public function __construct()
    {
        $this->url = env('SUPABASE_URL');
        $this->key = env('SUPABASE_KEY');
        $this->bucket = env('SUPABASE_STORAGE_BUCKET', 'verification-documents');
    }

    /**
     * Upload verification document to private storage.
     */
    public function upload($file, string $path): string
    {
        // Fallback to local storage if credentials are missing
        if (empty($this->url) || empty($this->key)) {
            Log::info('Supabase Storage: Credentials missing. Falling back to local storage.');
            
            $localPath = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
            return 'local://' . $localPath;
        }

        try {
            $mimeType = $file->getClientMimeType();
            $fileContent = file_get_contents($file->getRealPath());

            // Prepare Supabase storage upload API URL
            // POST /storage/v1/object/{bucket}/{filename}
            $uploadUrl = "{$this->url}/storage/v1/object/{$this->bucket}/{$path}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
            ])->attach(
                'file',
                $fileContent,
                basename($path),
                ['Content-Type' => $mimeType]
            )->post($uploadUrl);

            if ($response->failed()) {
                Log::error('Supabase Storage Upload Failed: ' . $response->body() . ' (Status: ' . $response->status() . ')');
                
                // Fallback on HTTP error
                $localPath = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
                return 'local://' . $localPath;
            }

            // Return the bucket and file path key
            return "supabase://{$this->bucket}/{$path}";
        } catch (\Throwable $e) {
            Log::error('Supabase Storage Upload Exception: ' . $e->getMessage());
            
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

        if (str_starts_with($path, 'supabase://')) {
            $cleanPath = str_replace('supabase://', '', $path);
            $parts = explode('/', $cleanPath, 2);
            if (count($parts) < 2) {
                return false;
            }
            $bucket = $parts[0];
            $filename = $parts[1];

            if (empty($this->url) || empty($this->key)) {
                return false;
            }

            try {
                // DELETE /storage/v1/object/{bucket}/{filename}
                $deleteUrl = "{$this->url}/storage/v1/object/{$bucket}/{$filename}";

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->key,
                ])->delete($deleteUrl);

                if ($response->failed()) {
                    Log::error('Supabase Storage Delete Failed: ' . $response->body() . ' (Status: ' . $response->status() . ')');
                    return false;
                }

                return true;
            } catch (\Throwable $e) {
                Log::error('Supabase Storage Delete Exception: ' . $e->getMessage());
                return false;
            }
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

        // Handle Supabase files
        $cleanPath = str_replace('supabase://', '', $path);
        
        // Credentials validation
        if (empty($this->url) || empty($this->key)) {
            abort(404, 'Supabase credentials missing and file is not local.');
        }

        try {
            // GET /storage/v1/object/authenticated/{bucket}/{filename}
            $fetchUrl = "{$this->url}/storage/v1/object/authenticated/{$cleanPath}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->key,
            ])->get($fetchUrl);

            if ($response->failed()) {
                Log::error('Supabase Storage Fetch Failed: ' . $response->body() . ' (Status: ' . $response->status() . ')');
                abort(404, 'Verification document not found in remote storage');
            }

            return [
                'content' => $response->body(),
                'mime' => $response->header('Content-Type') ?: 'application/octet-stream',
            ];
        } catch (\Throwable $e) {
            Log::error('Supabase Storage Fetch Exception: ' . $e->getMessage());
            abort(500, 'Error downloading document from remote storage');
        }
    }
}
