<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureValidSanctumCookie;
use App\Http\Middleware\LogActivity;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;


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
        // Exclude semua route login dari CSRF verification
        // sehingga login bisa langsung tanpa fetch /sanctum/csrf-cookie terlebih dahulu
        $middleware->validateCsrfTokens(except: [
            'api/auth/mhs/login',
            'api/auth/dosen/login',
            'api/auth/staff/login',
        ]);

        // Named middleware aliases
        // Catatan: 'admin' dan 'staff' alias dihapus — gunakan 'role:admin' / 'role:admin,staff'
        $middleware->alias([
            'role'           => EnsureRole::class,
            'sanctum.cookie' => EnsureValidSanctumCookie::class,
            'log.activity'   => LogActivity::class,
        ]);

        // Middleware group untuk Sanctum SPA Cookie (digunakan di api.php)
        // Catatan: EnsureFrontendRequestsAreStateful TIDAK dipakai karena secara internal
        // ia menambahkan AuthenticateSession yang membandingkan password_hash lintas guard
        // (web vs mahasiswa_web/dosen_web) sehingga session admin bisa di-flush saat
        // ada API call dari browser yang sama.
        $middleware->appendToGroup('sanctum.spa', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            // ValidateCsrfToken::class,
            // EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 401 — Belum login / session expired, kembalikan JSON bukan redirect ke halaman web
        $exceptions->render(function (AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. Silakan login terlebih dahulu.',
                    'error'   => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        // 422 — Validasi gagal
        $exceptions->render(function (ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data tidak valid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

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
