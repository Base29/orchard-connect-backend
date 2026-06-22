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

            // 3. Allow support ticket creation and tracking
            if ($request->is('api/support/tickets') || $request->is('api/support/tickets/track/*')) {
                return $next($request);
            }

            // 4. Allow access to authenticated admins/moderators
            // Note: Safely resolve the user without triggering session errors in stateless API routes.
            $user = null;
            try {
                if ($request->hasSession()) {
                    $user = $request->user();
                } else {
                    $user = auth('sanctum')->user();
                }
            } catch (\Throwable $e) {
                // Ignore session or auth resolution errors in stateless contexts
            }

            if ($user) {
                try {
                    if ($user->hasRole(['superadmin', 'community-admin', 'marketplace-moderator', 'content-moderator'], 'web')) {
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
