<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Get the redirect URL for the chosen OAuth provider.
     *
     * GET /api/auth/{provider}/redirect
     */
    public function redirectToProvider(string $provider): RedirectResponse
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(400, 'Unsupported authentication provider.');
        }

        try {
            return Socialite::driver($provider)
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            $frontendUrl = $this->getFrontendUrl();
            return redirect($frontendUrl . '/auth/login?error=oauth_failed');
        }
    }

    /**
     * Handle the callback payload from the OAuth provider.
     *
     * GET /api/auth/{provider}/callback
     */
    public function handleProviderCallback(string $provider): RedirectResponse
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(400, 'Unsupported auth provider');
        }

        $frontendUrl = $this->getFrontendUrl();

        try {
            // Retrieve user parameters statelessly from OAuth channel
            $socialUser = Socialite::driver($provider)->stateless()->user();
            
            $email = $socialUser->getEmail();

            // Validate that we got an email address
            if (empty($email)) {
                \Illuminate\Support\Facades\Log::warning("OAuth login failed for provider {$provider}: Email was not provided by the social account.");
                return redirect($frontendUrl . '/auth/login?error=email_required');
            }

            $user = DB::transaction(function () use ($socialUser, $provider, $email) {
                // 1. Search for existing social account link
                $socialAccount = SocialAccount::where('provider', $provider)
                    ->where('provider_user_id', $socialUser->getId())
                    ->first();

                if ($socialAccount) {
                    // Return associated user
                    return $socialAccount->user;
                }

                // 2. Fallback: Search user by email match
                $user = User::where('email', $email)->first();

                if (!$user) {
                    // 3. Create fresh user account
                    $user = User::create([
                        'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Resident',
                        'email' => $email,
                        'avatar_url' => $socialUser->getAvatar(),
                        'password' => null, // Social-only account initially
                        'status' => 'active',
                    ]);

                    try {
                        $user->sendCustomEmailVerificationNotification();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('OAuth verification email failed: ' . $e->getMessage());
                    }
                }

                // Link new social provider account
                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_user_id' => $socialUser->getId(),
                    'token' => $socialUser->token,
                    'avatar_url' => $socialUser->getAvatar(),
                ]);

                return $user;
            });

            if ($user->status !== 'active') {
                return redirect($frontendUrl . '/auth/login?error=account_suspended');
            }

            // Generate Sanctum access token for the authenticated resident session
            $token = $user->createToken('community_auth_token')->plainTextToken;

            // Check if resident profile is populated
            $profileComplete = $user->residentProfile()->exists();

            // Redirect back to frontend OAuth callback intercept route
            $redirectUrl = sprintf(
                '%s/auth/callback?token=%s&profile_complete=%s',
                $frontendUrl,
                $token,
                $profileComplete ? 'true' : 'false'
            );

            return redirect($redirectUrl);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("OAuth callback failed for provider {$provider}: " . $e->getMessage(), [
                'exception' => $e
            ]);
            return redirect($frontendUrl . '/auth/login?error=oauth_failed');
        }
    }

    /**
     * Determine the frontend URL dynamically.
     */
    protected function getFrontendUrl(): string
    {
        try {
            $frontendUrl = config('app.frontend_url');

            if (empty($frontendUrl)) {
                return request()->getSchemeAndHttpHost();
            }

            // If FRONTEND_URL is not configured, or if it points to localhost but the request is accessed
            // from a different host (like a mobile device on LAN or a demo server domain), resolve it dynamically.
            if (is_string($frontendUrl) && Str::contains($frontendUrl, 'localhost') && request()->getHost() !== 'localhost') {
                return request()->getSchemeAndHttpHost();
            }

            return $frontendUrl;
        } catch (\Throwable $e) {
            return request()->getSchemeAndHttpHost();
        }
    }
}
