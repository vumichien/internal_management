<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthenticated user attempted to access role-protected route', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'required_roles' => $roles,
            ]);

            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has any of the required roles
        if (!empty($roles) && !in_array($user->role, $roles)) {
            Log::warning('User with insufficient privileges attempted to access protected route', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Return appropriate response based on request type
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Insufficient privileges to access this resource.',
                    'required_roles' => $roles,
                    'user_role' => $user->role,
                ], 403);
            }

            abort(403, 'Insufficient privileges to access this resource.');
        }

        // Log successful role-based access for audit trail
        Log::info('User accessed role-protected route', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'required_roles' => $roles,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
        ]);

        return $next($request);
    }
} 