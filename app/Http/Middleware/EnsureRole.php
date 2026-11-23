<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware dinamis: izinkan akses berdasarkan role tertentu.
 *
 * Penggunaan di route:
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,staff')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = auth()->guard('staff_web')->user() ?? auth()->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Silakan login terlebih dahulu.',
                    'error' => 'UNAUTHENTICATED',
                ], 401);
            }

            return redirect('/login');
        }

        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengakses endpoint ini.',
                    'error' => 'FORBIDDEN',
                ], 403);
            }

            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
