<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request
        $this->logRequest($request);
        
        $response = $next($request);
        
        // Log response
        $this->logResponse($request, $response, $startTime);
        
        return $response;
    }
    
    /**
     * Log incoming request details
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'headers' => $this->getFilteredHeaders($request),
        ];
        
        // Only log request body for non-GET requests and exclude sensitive data
        if (!$request->isMethod('GET')) {
            $logData['body'] = $request->except([
                'password',
                'password_confirmation',
                'current_password',
                '_token',
            ]);
        }
        
        Log::channel('api')->info('Incoming request', $logData);
    }
    
    /**
     * Log response details
     */
    private function logResponse(Request $request, Response $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => auth()->id(),
        ];
        
        // Log level based on status code
        $logLevel = $this->getLogLevel($response->getStatusCode());
        
        Log::channel('api')->{$logLevel}('Request completed', $logData);
        
        // Log slow requests
        if ($duration > 1000) { // More than 1 second
            Log::channel('api')->warning('Slow request detected', array_merge($logData, [
                'threshold_ms' => 1000,
            ]));
        }
    }
    
    /**
     * Get filtered headers (exclude sensitive information)
     */
    private function getFilteredHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        
        // Remove sensitive headers
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
        ];
        
        foreach ($sensitiveHeaders as $header) {
            unset($headers[$header]);
        }
        
        return $headers;
    }
    
    /**
     * Get appropriate log level based on status code
     */
    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } else {
            return 'info';
        }
    }
} 