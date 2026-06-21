<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePoliciesAccepted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->hasRole('superadmin')) {
            // Exempt these specific paths from policy checking
            $exemptRoutes = [
                'api/user',
                'api/policies/accept',
                'api/broadcasting/auth',
            ];

            if ($request->is($exemptRoutes)) {
                return $next($request);
            }

            if (!$user->policies_accepted) {
                return response()->json([
                    'error' => 'POLICIES_NOT_ACCEPTED',
                    'message' => 'You must agree to the platform policies to continue.'
                ], 403);
            }
        }

        return $next($request);
    }
}
