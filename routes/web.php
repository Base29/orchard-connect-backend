<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DocumentProxyController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/document-view', [DocumentProxyController::class, 'view'])
    ->name('admin.document.view')
    ->middleware(['web', 'auth']);
