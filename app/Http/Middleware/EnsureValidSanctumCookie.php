<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureValidSanctumCookie
{
    /**
     * Handle an incoming request untuk Sanctum Cookie Authentication.
     * Middleware ini memastikan user sudah terautentikasi via Sanctum cookie session.
     */
    public function handle(Request $request, Closure $next)
    {
        // Cek apakah user sudah authenticated via salah satu guard session/Sanctum
        $user = Auth::guard('mahasiswa_web')->user()
            ?? Auth::guard('dosen_web')->user()
            ?? Auth::guard('mahasiswa')->user()
            ?? Auth::guard('dosen')->user()
            ?? Auth::guard('sanctum')->user()
            ?? $request->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Silakan login terlebih dahulu.',
            ], 401);
        }

        // Pastikan guard menentukan tipe Mahasiswa atau Dosen
        $isMahasiswa = isset($user->nim) && ! empty($user->nim);
        $isDosen = isset($user->nik) && ! empty($user->nik) && isset($user->nama_dosen);

        if (! ($isMahasiswa || $isDosen)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid user type. User harus Mahasiswa atau Dosen.',
            ], 403);
        }

        // Set user ke request supaya controller bisa mengakses user yang valid
        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
