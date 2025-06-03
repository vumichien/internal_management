<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $this->logException($e);
        });

        // Handle authentication exceptions
        $this->renderable(function (AuthenticationException $e, Request $request) {
            Log::channel('auth')->warning('Authentication failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'exception' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        });

        // Handle validation exceptions
        $this->renderable(function (ValidationException $e, Request $request) {
            Log::channel('api')->info('Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except($this->dontFlash),
                'url' => $request->fullUrl(),
            ]);
        });

        // Handle model not found exceptions
        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            Log::channel('database')->warning('Model not found', [
                'model' => $e->getModel(),
                'ids' => $e->getIds(),
                'url' => $request->fullUrl(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        // Handle 404 exceptions
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            Log::channel('security')->info('404 Not Found', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });
    }

    /**
     * Log exception details with context
     */
    private function logException(Throwable $exception): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        // Add request context if available
        if (request()) {
            $context['request'] = [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ];
        }

        // Log to appropriate channel based on exception type
        $channel = $this->getLogChannelForException($exception);
        
        Log::channel($channel)->error('Exception occurred', $context);

        // Log critical errors to security channel as well
        if ($this->isCriticalException($exception)) {
            Log::channel('security')->critical('Critical exception occurred', $context);
        }
    }

    /**
     * Determine the appropriate log channel for an exception
     */
    private function getLogChannelForException(Throwable $exception): string
    {
        // Database related exceptions
        if (str_contains(get_class($exception), 'Database') || 
            str_contains(get_class($exception), 'Query')) {
            return 'database';
        }

        // Authentication related exceptions
        if ($exception instanceof AuthenticationException) {
            return 'auth';
        }

        // Security related exceptions
        if ($exception instanceof HttpException && $exception->getStatusCode() >= 400) {
            return 'security';
        }

        // Default to main log
        return 'daily';
    }

    /**
     * Determine if an exception is critical
     */
    private function isCriticalException(Throwable $exception): bool
    {
        $criticalExceptions = [
            'Error',
            'ParseError',
            'TypeError',
            'FatalError',
        ];

        foreach ($criticalExceptions as $criticalType) {
            if (str_contains(get_class($exception), $criticalType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        Log::channel('auth')->warning('Unauthenticated access attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'guards' => $exception->guards(),
        ]);

        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
