<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureIsStaff
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check() || ! auth()->user()->isStaff()) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
