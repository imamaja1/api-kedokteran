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
        if (! auth()->check()) {
            return redirect('/login');
        }

        if (! in_array(auth()->user()->role, $roles)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
