<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SessionService $sessionService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionService = new SessionService();
        $this->user = User::factory()->create(['remember_token' => null]);
    }

    public function test_regenerate_session_updates_session_id()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        $originalSessionId = $request->session()->getId();
        
        $this->sessionService->regenerateSession($request);
        
        $newSessionId = $request->session()->getId();
        $this->assertNotEquals($originalSessionId, $newSessionId);
    }

    public function test_invalidate_session_clears_session_data()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        // Add some session data
        $request->session()->put('test_key', 'test_value');
        $this->assertTrue($request->session()->has('test_key'));
        
        $this->sessionService->invalidateSession($request);
        
        // Session should be cleared
        $this->assertFalse($request->session()->has('test_key'));
    }

    public function test_is_session_valid_returns_false_for_unauthenticated_user()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        $this->assertFalse($this->sessionService->isSessionValid($request));
    }

    public function test_is_session_valid_returns_true_for_authenticated_user()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        // Ensure session has a token
        $request->session()->regenerateToken();
        
        Auth::login($this->user);
        
        $this->assertTrue($this->sessionService->isSessionValid($request));
    }

    public function test_set_remember_token_creates_token_when_remember_is_true()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        Auth::login($this->user);
        
        // Clear any existing remember token
        $this->user->remember_token = null;
        $this->user->save();
        
        $this->assertNull($this->user->remember_token);
        
        $this->sessionService->setRememberToken($request, true);
        
        $this->user->refresh();
        $this->assertNotNull($this->user->remember_token);
        $this->assertEquals(60, strlen($this->user->remember_token));
    }

    public function test_set_remember_token_does_nothing_when_remember_is_false()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        Auth::login($this->user);
        
        // Clear any existing remember token
        $this->user->remember_token = null;
        $this->user->save();
        
        $this->assertNull($this->user->remember_token);
        
        $this->sessionService->setRememberToken($request, false);
        
        $this->user->refresh();
        $this->assertNull($this->user->remember_token);
    }

    public function test_clear_remember_token_removes_token()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        Auth::login($this->user);
        
        // Set a remember token first
        $this->user->remember_token = 'test_token';
        $this->user->save();
        
        $this->sessionService->clearRememberToken($request);
        
        $this->user->refresh();
        $this->assertNull($this->user->remember_token);
    }

    public function test_get_session_info_returns_correct_data()
    {
        $request = Request::create('/test');
        $request->setLaravelSession($this->app['session.store']);
        
        Auth::login($this->user);
        
        $sessionInfo = $this->sessionService->getSessionInfo($request);
        
        $this->assertIsArray($sessionInfo);
        $this->assertArrayHasKey('session_id', $sessionInfo);
        $this->assertArrayHasKey('user_id', $sessionInfo);
        $this->assertArrayHasKey('is_authenticated', $sessionInfo);
        $this->assertArrayHasKey('session_lifetime', $sessionInfo);
        $this->assertArrayHasKey('csrf_token', $sessionInfo);
        $this->assertArrayHasKey('ip_address', $sessionInfo);
        $this->assertArrayHasKey('user_agent', $sessionInfo);
        
        $this->assertEquals($this->user->id, $sessionInfo['user_id']);
        $this->assertTrue($sessionInfo['is_authenticated']);
    }

    public function test_session_service_methods_exist()
    {
        $this->assertTrue(method_exists($this->sessionService, 'regenerateSession'));
        $this->assertTrue(method_exists($this->sessionService, 'invalidateSession'));
        $this->assertTrue(method_exists($this->sessionService, 'isSessionValid'));
        $this->assertTrue(method_exists($this->sessionService, 'setRememberToken'));
        $this->assertTrue(method_exists($this->sessionService, 'clearRememberToken'));
        $this->assertTrue(method_exists($this->sessionService, 'logSessionActivity'));
        $this->assertTrue(method_exists($this->sessionService, 'getSessionInfo'));
        $this->assertTrue(method_exists($this->sessionService, 'forceLogoutAllSessions'));
    }
} 