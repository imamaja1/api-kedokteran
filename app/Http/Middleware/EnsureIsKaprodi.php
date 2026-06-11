<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\ProgramStudi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsKaprodi
{
    public function handle(Request $request, Closure $next): Response
    {
        $dosen = Auth::guard('dosen_web')->user();

        if (! $dosen) {
            return ApiResponse::unauthorized();
        }

        $isKaprodi = ProgramStudi::where('kode_dosen_kaprodi', $dosen->kode_dosen)
            ->exists();

        if (! $isKaprodi) {
            return ApiResponse::error('Anda tidak memiliki akses sebagai Kaprodi.', 403);
        }

        return $next($request);
    }
}
