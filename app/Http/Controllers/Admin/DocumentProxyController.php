<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;

class DocumentProxyController extends Controller
{
    protected SupabaseStorageService $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Proxies private document downloads for authenticated staff members.
     */
    public function view(Request $request)
    {
        // Restrict access to active administrative users only
        $user = auth()->user();
        if (!$user || !$user->isActive() || !$user->hasAnyRole(['Super Admin', 'Feed Moderator', 'Marketplace Moderator'])) {
            abort(403, 'Unauthorized access to administrative documents.');
        }

        $path = $request->query('path');
        if (empty($path)) {
            abort(400, 'Document path is required.');
        }

        if ($path === 'purged') {
            abort(410, 'The document was purged to protect resident privacy.');
        }

        $file = $this->storage->get($path);

        return response($file['content'], 200)
            ->header('Content-Type', $file['mime'])
            ->header('Content-Disposition', 'inline')
            ->header('Cache-Control', 'private, max-age=1800');
    }
}
