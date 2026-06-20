<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResidentProfile;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\VerifyEmailMailable;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $verifiedEmailUser;
    private User $unverifiedEmailUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with verified email
        $this->verifiedEmailUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        ResidentProfile::create([
            'user_id' => $this->verifiedEmailUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '100',
            'user_type' => 'owner',
            'is_verified' => true,
            'status' => 'approved',
        ]);

        // Create user with unverified email
        $this->unverifiedEmailUser = User::factory()->create([
            'email_verified_at' => null,
        ]);
        ResidentProfile::create([
            'user_id' => $this->unverifiedEmailUser->id,
            'phase' => 'Phase 2',
            'block' => 'Block B',
            'house_number' => '200',
            'user_type' => 'tenant',
            'is_verified' => true,
            'status' => 'approved',
        ]);
    }

    /**
     * Unverified email users cannot perform mutating actions protected by verified.email middleware.
     */
    public function test_unverified_email_user_is_blocked_from_mutating_actions(): void
    {
        $response = $this->actingAs($this->unverifiedEmailUser, 'sanctum')
            ->postJson('/api/listings', [
                'title' => 'iPhone 15 Pro',
                'description' => 'Mint condition, 256GB storage.',
                'price' => 120000,
                'category' => 'Electronics',
                'contact_whatsapp' => '+923001234567',
                'images' => [],
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'EMAIL_UNVERIFIED',
                'message' => 'Your email address is unverified.'
            ]);
    }

    /**
     * Verified email users can perform mutating actions protected by verified.email middleware.
     */
    public function test_verified_email_user_can_perform_mutating_actions(): void
    {
        $response = $this->actingAs($this->verifiedEmailUser, 'sanctum')
            ->postJson('/api/listings', [
                'title' => 'iPhone 15 Pro',
                'description' => 'Mint condition, 256GB storage.',
                'price' => 120000,
                'category' => 'Electronics',
                'contact_whatsapp' => '+923001234567',
                'images' => [],
            ]);

        $response->assertStatus(201);
    }

    /**
     * Users can request a resend of their verification email.
     */
    public function test_user_can_request_resend_verification_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->unverifiedEmailUser, 'sanctum')
            ->postJson('/api/email/verification-notification');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Verification link sent.'
            ]);

        Mail::assertSent(VerifyEmailMailable::class, function ($mail) {
            return $mail->hasTo($this->unverifiedEmailUser->email);
        });
    }

    /**
     * Verified users get a bad request response when requesting resend verification email.
     */
    public function test_verified_user_request_resend_verification_email_returns_bad_request(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->verifiedEmailUser, 'sanctum')
            ->postJson('/api/email/verification-notification');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Email is already verified.'
            ]);

        Mail::assertNotSent(VerifyEmailMailable::class);
    }

    /**
     * Users can verify their email address via the signed route.
     */
    public function test_user_can_verify_email_via_signed_route(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->unverifiedEmailUser->id, 'hash' => sha1($this->unverifiedEmailUser->email)]
        );

        $response = $this->get($verificationUrl);

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $response->assertRedirect($frontendUrl . '/dashboard?email_verified=true');

        $this->unverifiedEmailUser->refresh();
        $this->assertNotNull($this->unverifiedEmailUser->email_verified_at);
    }

    /**
     * Verification fails with an invalid signature.
     */
    public function test_verification_fails_with_invalid_signature(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->unverifiedEmailUser->id, 'hash' => 'wrong-hash']
        );

        $response = $this->get($verificationUrl);

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $response->assertRedirect($frontendUrl . '/auth/login?error=invalid_signature');

        $this->unverifiedEmailUser->refresh();
        $this->assertNull($this->unverifiedEmailUser->email_verified_at);
    }

    /**
     * Test that user registration sends exactly one verification email.
     */
    public function test_registration_sends_single_email(): void
    {
        Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        // Assert exactly one email was sent via Mailable
        Mail::assertSent(VerifyEmailMailable::class, 1);

        // Assert default notification was NOT sent
        $user = User::where('email', 'johndoe@example.com')->first();
        $this->assertNotNull($user);
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            [$user],
            \Illuminate\Auth\Notifications\VerifyEmail::class
        );
    }

    /**
     * Test that user registration dispatches the Registered event.
     */
    public function test_registration_dispatches_registered_event(): void
    {
        \Illuminate\Support\Facades\Event::fake([
            \Illuminate\Auth\Events\Registered::class
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        \Illuminate\Support\Facades\Event::assertDispatched(\Illuminate\Auth\Events\Registered::class);
    }
}

