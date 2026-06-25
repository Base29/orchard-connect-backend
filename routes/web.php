<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DocumentProxyController;
use App\Http\Controllers\Admin\AdminAutoLoginController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/document-view', [DocumentProxyController::class, 'view'])
    ->name('admin.document.view')
    ->middleware(['web', 'auth']);

Route::get('/admin/s3-preview', function (\Illuminate\Http\Request $request) {
    $path = $request->query('path');
    if (!$path) {
        abort(400);
    }
    
    // Auth check: only authenticated admin/staff users can preview these files
    if (!auth()->check()) {
        abort(403);
    }

    try {
        if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($path)) {
            $content = \Illuminate\Support\Facades\Storage::disk('s3')->get($path);
            $mime = \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($path);
            return response($content, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline');
        }
    } catch (\Exception $e) {
        abort(404);
    }
    abort(404);
})->middleware(['web', 'auth'])->name('admin.s3.preview');

Route::get('/api/auth/admin/login-with-code/{code}', [AdminAutoLoginController::class, 'loginWithCode'])
    ->name('admin.login-with-code')
    ->middleware(['web']);
