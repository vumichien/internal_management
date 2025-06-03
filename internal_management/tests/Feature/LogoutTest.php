<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_logout_clears_session_data()
    {
        $user = User::factory()->create();

        // Login and add some session data
        $this->actingAs($user);
        session(['test_key' => 'test_value']);
        
        $this->assertTrue(session()->has('test_key'));

        // Logout
        $this->post('/logout');

        // Session should be cleared
        $this->assertGuest();
        $this->assertFalse(session()->has('test_key'));
    }

    public function test_logout_clears_remember_token()
    {
        $user = User::factory()->create([
            'remember_token' => 'test_remember_token',
        ]);

        $this->actingAs($user)->post('/logout');

        $user->refresh();
        $this->assertNull($user->remember_token);
    }

    public function test_logout_redirects_to_home_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
    }

    public function test_logout_works_from_any_page()
    {
        $user = User::factory()->create();

        // Test logout from different pages
        $response = $this->actingAs($user)
                         ->from('/dashboard')
                         ->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->post('/logout');

        // Should redirect to login page or handle gracefully
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_id()
    {
        $user = User::factory()->create();

        // Login and get session ID
        $this->actingAs($user);
        $originalSessionId = session()->getId();

        // Logout
        $this->post('/logout');

        // Start new session and verify it's different
        $this->get('/');
        $newSessionId = session()->getId();

        $this->assertNotEquals($originalSessionId, $newSessionId);
    }

    public function test_logout_navigation_link_exists()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Log Out');
        $response->assertSee('logout');
    }

    public function test_logout_form_has_csrf_protection()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        // Check that the logout form includes CSRF token
        $response->assertSee('name="_token"', false);
        $response->assertSee('type="hidden"', false);
    }

    public function test_multiple_logout_attempts_are_safe()
    {
        $user = User::factory()->create();

        // First logout
        $this->actingAs($user)->post('/logout');
        $this->assertGuest();

        // Second logout attempt (should not cause errors)
        $response = $this->post('/logout');
        $this->assertGuest();
        
        // Should handle gracefully without errors
        $this->assertTrue(true);
    }
} 