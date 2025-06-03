<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user status is active
        if ($user->status !== 'active') {
            Log::warning('Inactive user attempted to access application', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_status' => $user->status,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Log out the inactive user
            Auth::logout();
            
            // Invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Return appropriate response based on request type
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account is not active. Please contact an administrator.',
                    'status' => $user->status,
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not active. Please contact an administrator.',
            ]);
        }

        return $next($request);
    }
} 