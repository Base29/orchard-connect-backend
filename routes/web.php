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

Route::get('/api/auth/admin/login-with-code/{code}', [AdminAutoLoginController::class, 'loginWithCode'])
    ->name('admin.login-with-code')
    ->middleware(['web']);
