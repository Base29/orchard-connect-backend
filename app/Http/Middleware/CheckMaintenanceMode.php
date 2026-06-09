<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled in database settings
        $isMaintenance = Setting::getValue('maintenance_mode', false);

        if ($isMaintenance) {
            // 1. Allow public maintenance status checks
            if ($request->is('api/maintenance-status')) {
                return $next($request);
            }

            // 2. Allow broadcasting authentication
            if ($request->is('api/broadcasting/auth')) {
                return $next($request);
            }

            // 3. Allow access to authenticated admins/moderators
            // Note: Sanctum authenticator should have already resolved the user if a bearer token was sent.
            $user = $request->user();
            if ($user) {
                try {
                    if ($user->hasAnyRole(['Super Admin', 'Feed Moderator', 'Marketplace Moderator'])) {
                        return $next($request);
                    }
                } catch (\Throwable $e) {
                    // Fallback if Spatie roles are not loaded/configured properly in the context
                }
            }

            // 4. Block all other requests
            return response()->json([
                'message' => 'Platform is currently undergoing scheduled maintenance. Please try again later.'
            ], 503);
        }

        return $next($request);
    }
}
