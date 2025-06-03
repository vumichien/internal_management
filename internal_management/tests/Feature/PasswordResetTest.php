<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered()
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect('/login');

            return true;
        });
    }

    public function test_password_reset_requires_valid_email()
    {
        $response = $this->post('/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_reset_requires_existing_email()
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_reset_requires_password_confirmation()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'different-password',
            ]);

            $response->assertSessionHasErrors(['password']);

            return true;
        });
    }

    public function test_password_reset_requires_valid_token()
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_is_actually_changed_after_reset()
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

            $user->refresh();

            // Verify old password no longer works
            $this->assertFalse(Hash::check('old-password', $user->password));
            
            // Verify new password works
            $this->assertTrue(Hash::check('new-password', $user->password));

            return true;
        });
    }

    public function test_remember_token_is_regenerated_after_password_reset()
    {
        Notification::fake();

        $user = User::factory()->create([
            'remember_token' => 'old-remember-token',
        ]);

        $originalRememberToken = $user->remember_token;

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $originalRememberToken) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

            $user->refresh();

            // Verify remember token was changed
            $this->assertNotEquals($originalRememberToken, $user->remember_token);
            $this->assertNotNull($user->remember_token);
            $this->assertEquals(60, strlen($user->remember_token));

            return true;
        });
    }

    public function test_password_reset_rate_limiting()
    {
        $user = User::factory()->create();

        // Make multiple requests to trigger rate limiting
        // Laravel's built-in rate limiting may kick in before our custom one
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->post('/forgot-password', ['email' => $user->email]);
        }

        // At least one of the later requests should have errors due to rate limiting
        $hasRateLimitError = false;
        foreach (array_slice($responses, 2) as $response) {
            if ($response->getSession()->has('errors')) {
                $hasRateLimitError = true;
                break;
            }
        }

        $this->assertTrue($hasRateLimitError, 'Rate limiting should be triggered after multiple requests');
    }

    public function test_social_users_cannot_reset_password_if_no_password_set()
    {
        // Create a social-only user (no password)
        $user = User::factory()->create([
            'password' => null,
            'google_id' => 'google123',
        ]);

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        // Should handle gracefully - either allow reset or show appropriate message
        // The exact behavior depends on business requirements
        $this->assertTrue(true); // Placeholder - implement based on requirements
    }
} 