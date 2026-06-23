<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AdminAutoLoginController extends Controller
{
    /**
     * Exchange Sanctum authenticated token for a short-lived one-time code.
     */
    public function exchangeToken(Request $request)
    {
        $user = $request->user();

        // 1. Ensure user is active
        if (!$user || !$user->isActive()) {
            return response()->json(['message' => 'User is not active.'], 403);
        }

        // 2. Ensure they are a verified resident (excluding superadmin if they bypass residency check)
        $isSuperAdmin = $user->hasRole('superadmin');
        if (!$isSuperAdmin && !$user->isResidencyVerified()) {
            return response()->json(['message' => 'Residency verification is required to access the admin panel.'], 403);
        }

        // 3. Ensure user has permission/role to access Filament panel
        if (!$user->hasAnyRole(['superadmin', 'community-admin', 'marketplace-moderator', 'content-moderator', 'support-staff'])) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // 4. Generate a secure, short-lived code
        $code = Str::random(40);
        
        // 5. Store user ID in cache for 60 seconds
        Cache::put('admin_autologin_code_' . $code, $user->id, 60);

        // 6. Generate the login web URL
        $redirectUrl = route('admin.login-with-code', ['code' => $code]);

        return response()->json([
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Authenticate session using the one-time code and redirect to admin.
     */
    public function loginWithCode(Request $request, $code)
    {
        $userId = Cache::pull('admin_autologin_code_' . $code);

        if (!$userId) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect($frontendUrl . '/auth/login?error=invalid_login_code');
        }

        $user = User::find($userId);
        if (!$user || !$user->isActive()) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect($frontendUrl . '/auth/login?error=unauthorized_user');
        }

        // Log the user in to the web guard session
        Auth::guard('web')->login($user);

        // Regenerate session to protect against session fixation
        $request->session()->regenerate();

        return redirect('/admin');
    }
}
