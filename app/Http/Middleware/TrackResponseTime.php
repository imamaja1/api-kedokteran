<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackResponseTime
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = round(($endTime - $startTime) * 1000, 2);
        $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);

        // Add response headers
        $response->header('X-Response-Time-Ms', $duration);
        $response->header('X-Memory-Used-MB', $memoryUsed);

        // Log slow requests (> 500ms)
        if ($duration > 500) {
            Log::warning('⚠️  Slow request detected', [
                'path' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'memory_mb' => $memoryUsed,
                'status_code' => $response->status(),
            ]);
        }

        // Log all requests in debug mode
        if (config('app.debug')) {
            Log::debug('📊 API Request', [
                'path' => $request->path(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'memory_mb' => $memoryUsed,
                'status_code' => $response->status(),
            ]);
        }

        return $response;
    }
}
