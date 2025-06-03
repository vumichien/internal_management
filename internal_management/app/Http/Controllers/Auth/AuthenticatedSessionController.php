<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private SessionService $sessionService
    ) {}

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Use SessionService for secure session management
        $this->sessionService->regenerateSession($request);

        // Update last login information for the authenticated user
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user) {
            $user->updateLastLogin($request->ip());
            
            // Handle remember me functionality
            $this->sessionService->setRememberToken($request, $request->boolean('remember'));
            
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'login_method' => 'traditional',
                'remember_me' => $request->boolean('remember'),
            ]);

            // Log session activity
            $this->sessionService->logSessionActivity($request, 'login', [
                'login_method' => 'traditional',
                'remember_me' => $request->boolean('remember'),
            ]);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if ($user) {
            Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Log session activity before logout
            $this->sessionService->logSessionActivity($request, 'logout');
            
            // Clear remember token
            $this->sessionService->clearRememberToken($request);
        }

        Auth::guard('web')->logout();

        // Use SessionService for secure session invalidation
        $this->sessionService->invalidateSession($request);

        return redirect('/');
    }
}
