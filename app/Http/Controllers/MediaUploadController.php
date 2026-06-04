<?php
 
namespace App\Http\Controllers;
 
use App\Services\S3StorageService;
use Illuminate\Http\Request;
 
class MediaUploadController extends Controller
{
    protected S3StorageService $s3Storage;
 
    public function __construct(S3StorageService $s3Storage)
    {
        $this->s3Storage = $s3Storage;
    }
 
    /**
     * Upload user shared media for posts/listings to AWS S3 (or fallback).
     */
    public function upload(Request $request)
    {
        // 1. Guard against unverified residents
        $user = $request->user();
        if (!$user->residentProfile || !$user->residentProfile->is_verified) {
            return response()->json([
                'message' => 'Action locked. Residency verification required to upload files.'
            ], 403);
        }
 
        // 2. Validate request parameters
        $validated = $request->validate([
            'file' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB
            'type' => 'required|string|in:post,listing',
        ]);
 
        $file = $request->file('file');
        $type = $validated['type'];
        
        // 3. Generate structured folder path
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $path = "{$type}s/{$user->id}/{$filename}"; // e.g. posts/user_id/12345678_abcd.jpg
 
        // 4. Upload and return public URL
        $url = $this->s3Storage->upload($file, $path);
 
        return response()->json([
            'url' => $url,
        ]);
    }
}
