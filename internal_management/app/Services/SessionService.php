<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SessionService
{
    /**
     * Regenerate session for security after login
     */
    public function regenerateSession(Request $request): void
    {
        $request->session()->regenerate();
        
        Log::info('Session regenerated for security', [
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Invalidate session and clear all data
     */
    public function invalidateSession(Request $request): void
    {
        $sessionId = $request->session()->getId();
        $userId = Auth::id();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Log::info('Session invalidated', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Check if session is expired or invalid
     */
    public function isSessionValid(Request $request): bool
    {
        // Check if session exists and is not expired
        if (!$request->session()->has('_token')) {
            return false;
        }

        // Check if user is still authenticated
        if (!Auth::check()) {
            return false;
        }

        // Additional security checks can be added here
        return true;
    }

    /**
     * Set remember token for persistent authentication
     */
    public function setRememberToken(Request $request, bool $remember = false): void
    {
        if ($remember && Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $user->setRememberToken(Str::random(60));
            $user->save();
            
            Log::info('Remember token set for user', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * Clear remember token
     */
    public function clearRememberToken(Request $request): void
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $user->setRememberToken(null);
            $user->save();
            
            Log::info('Remember token cleared for user', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * Log session activity for security monitoring
     */
    public function logSessionActivity(Request $request, string $activity, array $additionalData = []): void
    {
        $logData = array_merge([
            'activity' => $activity,
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $additionalData);

        Log::info('Session activity logged', $logData);
    }

    /**
     * Get session information for debugging/monitoring
     */
    public function getSessionInfo(Request $request): array
    {
        return [
            'session_id' => $request->session()->getId(),
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check(),
            'session_lifetime' => config('session.lifetime'),
            'last_activity' => $request->session()->get('_previous.url'),
            'csrf_token' => $request->session()->token(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * Force logout all sessions for a user (useful for security incidents)
     */
    public function forceLogoutAllSessions(int $userId): void
    {
        // This would require database session storage to fully implement
        // For now, we'll log the action
        Log::warning('Force logout requested for user', [
            'user_id' => $userId,
            'action' => 'force_logout_all_sessions',
            'timestamp' => now()->toISOString(),
        ]);
    }
} 