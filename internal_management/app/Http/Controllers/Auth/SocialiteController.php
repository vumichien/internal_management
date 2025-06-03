<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthProviderFactory;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\InvalidStateException;

class SocialiteController extends Controller
{
    public function __construct(
        private SessionService $sessionService
    ) {}

    /**
     * Redirect to the social provider authentication page.
     */
    public function redirect(string $provider): RedirectResponse
    {
        try {
            $authProvider = SocialAuthProviderFactory::make($provider);
            
            if (!$authProvider->isEnabled()) {
                Log::warning('Attempted to use disabled social provider', [
                    'provider' => $provider,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
                
                return redirect('/login')->withErrors([
                    'error' => ucfirst($provider) . ' authentication is currently disabled.'
                ]);
            }

            Log::info('Social authentication redirect initiated', [
                'provider' => $provider,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            
            return redirect($authProvider->getRedirectUrl());
            
        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid social provider attempted', [
                'provider' => $provider,
                'ip' => request()->ip(),
                'error' => $e->getMessage()
            ]);
            
            return redirect('/login')->withErrors([
                'error' => 'Invalid authentication provider.'
            ]);
        } catch (\Exception $e) {
            Log::error('Social authentication redirect failed', [
                'provider' => $provider,
                'ip' => request()->ip(),
                'error' => $e->getMessage()
            ]);
            
            return redirect('/login')->withErrors([
                'error' => 'Authentication service temporarily unavailable. Please try again.'
            ]);
        }
    }

    /**
     * Handle the callback from the social provider.
     */
    public function callback(string $provider): RedirectResponse
    {
        try {
            $authProvider = SocialAuthProviderFactory::make($provider);
            
            if (!$authProvider->isEnabled()) {
                Log::warning('Callback received for disabled social provider', [
                    'provider' => $provider,
                    'ip' => request()->ip()
                ]);
                
                return redirect('/login')->withErrors([
                    'error' => ucfirst($provider) . ' authentication is currently disabled.'
                ]);
            }

            // Get user data from the social provider
            $socialUser = $authProvider->handleCallback();
            
            // Validate that we received required user information
            if (!$socialUser->getEmail()) {
                Log::warning('Social authentication missing email', [
                    'provider' => $provider,
                    'social_user_id' => $socialUser->getId()
                ]);
                
                return redirect('/login')->withErrors([
                    'error' => 'Unable to retrieve email from ' . ucfirst($provider) . '. Please ensure your email is public.'
                ]);
            }

            // Find or create user using the provider
            $user = $authProvider->findOrCreateUser($socialUser);
            
            // Update last login information
            $user->updateLastLogin(request()->ip());
            
            // Log the user in
            Auth::login($user, true);
            
            // Use SessionService for secure session management
            $this->sessionService->regenerateSession(request());
            
            // Set remember token for persistent login
            $this->sessionService->setRememberToken(request(), true);
            
            // Log session activity
            $this->sessionService->logSessionActivity(request(), 'social_login', [
                'provider' => $provider,
                'social_id' => $socialUser->getId(),
            ]);

            Log::info('Social authentication successful', [
                'provider' => $provider,
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return redirect()->intended('/dashboard');
            
        } catch (InvalidStateException $e) {
            Log::warning('Social authentication state mismatch', [
                'provider' => $provider,
                'ip' => request()->ip(),
                'error' => $e->getMessage()
            ]);
            
            return redirect('/login')->withErrors([
                'error' => 'Authentication session expired. Please try again.'
            ]);
            
        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid social provider in callback', [
                'provider' => $provider,
                'ip' => request()->ip(),
                'error' => $e->getMessage()
            ]);
            
            return redirect('/login')->withErrors([
                'error' => 'Invalid authentication provider.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Social authentication failed', [
                'provider' => $provider,
                'ip' => request()->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/login')->withErrors([
                'error' => 'Authentication failed. Please try again or contact support.'
            ]);
        }
    }

    /**
     * Get available social authentication providers for the login page
     */
    public function getAvailableProviders(): array
    {
        return SocialAuthProviderFactory::getEnabledProviders();
    }

    /**
     * Get provider status information (for admin/debugging)
     */
    public function getProviderStatus(): array
    {
        return SocialAuthProviderFactory::getProviderStatus();
    }
}

