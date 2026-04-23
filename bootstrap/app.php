<?php

use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsStaff;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureValidSanctumCookie;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')->group(base_path('routes/mahasiswa.php'));
            Route::middleware('api')->group(base_path('routes/dosen.php'));
            Route::middleware('api')->group(base_path('routes/staff.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Named middleware aliases
        $middleware->alias([
            'staff'          => EnsureIsStaff::class,
            'admin'          => EnsureIsAdmin::class,
            'role'           => EnsureRole::class,
            'sanctum.cookie' => EnsureValidSanctumCookie::class,
        ]);

        // Middleware group untuk Sanctum SPA Cookie (digunakan di api.php)
        $middleware->appendToGroup('sanctum.spa', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 404 — Route tidak ditemukan
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Endpoint tidak ditemukan.',
                    'error'   => 'NOT_FOUND',
                ], 404);
            }
        });

        // 405 — HTTP method tidak diizinkan
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Method tidak diizinkan untuk endpoint ini.',
                    'error'   => 'METHOD_NOT_ALLOWED',
                ], 405);
            }
        });

        // 419 — CSRF / XSRF token tidak sesuai saat login
        $exceptions->render(function (TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Token tidak sesuai. Silakan ambil ulang CSRF token melalui GET /sanctum/csrf-cookie lalu ulangi permintaan.',
                    'error'   => 'TOKEN_MISMATCH',
                ], 419);
            }
        });
    })->create();
