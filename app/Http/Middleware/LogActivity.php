<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Optimized: Skip logging for health checks and OPTIONS requests
        if (in_array($request->method(), ['OPTIONS', 'HEAD']) || 
            str_starts_with($request->path(), 'sanctum/csrf-cookie')) {
            return $response;
        }

        try {
            foreach (['mahasiswa_web', 'dosen_web', 'staff_web', 'web'] as $guard) {
                if (! auth()->guard($guard)->check()) {
                    continue;
                }

                $user = auth()->guard($guard)->user();

                [$userType, $userId] = match ($guard) {
                    'mahasiswa_web' => ['mahasiswa', $user->nim ?? null],
                    'dosen_web'     => ['dosen',     $user->kode_dosen ?? null],
                    'staff_web', 'web' => ['staff',  (string) ($user->id ?? null)],
                    default         => [null, null],
                };

                $logData = [
                    'guard'       => $guard,
                    'user_id'     => $userId,
                    'user_type'   => $userType,
                    'method'      => $request->method(),
                    'path'        => $request->path(),
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                    'status_code' => $response->getStatusCode(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                // Defer insert hingga setelah response terkirim agar tidak menambah latensi request
                app()->terminating(function () use ($logData): void {
                    try {
                        \DB::table('activity_logs')->insert($logData);
                    } catch (\Throwable) {
                        // Kegagalan logging tidak boleh menghentikan response
                    }
                });

                break;
            }
        } catch (\Throwable) {
            // Kegagalan logging tidak boleh menghentikan response
        }

        return $response;
    }
}
