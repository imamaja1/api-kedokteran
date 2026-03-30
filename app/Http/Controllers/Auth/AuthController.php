<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DosenLoginRequest;
use App\Http\Requests\Auth\MhsLoginRequest;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login mahasiswa atau dosen.
     * Sanctum Cookie: setelah login, sesi disimpan di cookie laravel_session.
     */
    protected function requestHasSessionStore(Request $request): bool
    {
        return $request->hasSession() && $request->getSession() !== null;
    }

    public function mhs_login(MhsLoginRequest $request): JsonResponse
    {
        $nim = $request->nim;
        $password = $request->password;

        $user = Mahasiswa::where('nim', $nim)
            ->orWhere('email', $nim)
            ->first();

        if (! $user || ! Hash::check($password, $user->sandi)) {
            return response()->json([
                'status' => false,
                'message' => 'NIM atau password salah.',
            ], 401);
        }

        // Authenticate dengan guard mahasiswa_web
        Auth::guard('mahasiswa_web')->login($user);

        if (! $this->requestHasSessionStore($request)) {
            Auth::guard('mahasiswa_web')->logout();

            $payload = [
                'status' => false,
                'message' => 'Session not available. Use Sanctum SPA cookie auth (call /sanctum/csrf-cookie, send credentials, and ensure your domain is in SANCTUM_STATEFUL_DOMAINS).',
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'origin' => $request->headers->get('origin'),
                    'referer' => $request->headers->get('referer'),
                    'host' => $request->getHost(),
                    'has_session' => $this->requestHasSessionStore($request),
                    'cookie_names' => array_keys($request->cookies->all()),
                ];
            }

            return response()->json($payload, 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login Mahasiswa berhasil.',
            'data' => [
                'nim' => $user->nim,
                'nama' => $user->nama_mahasiswa,
                'email' => $user->email,
                'type' => 'mahasiswa',
            ],
        ]);
    }

    public function dosen_login(DosenLoginRequest $request): JsonResponse
    {
        $email = $request->email;
        $password = $request->password;

        $user = Dosen::where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->sandi_pengguna)) {
            return response()->json([
                'status' => false,
                'message' => 'Kode Dosen atau password salah.',
            ], 401);
        }

        // Authenticate dengan guard dosen_web
        Auth::guard('dosen_web')->login($user);

        if (! $this->requestHasSessionStore($request)) {
            Auth::guard('dosen_web')->logout();

            $payload = [
                'status' => false,
                'message' => 'Session not available. Use Sanctum SPA cookie auth (call /sanctum/csrf-cookie, send credentials, and ensure your domain is in SANCTUM_STATEFUL_DOMAINS).',
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'origin' => $request->headers->get('origin'),
                    'referer' => $request->headers->get('referer'),
                    'host' => $request->getHost(),
                    'has_session' => $this->requestHasSessionStore($request),
                    'cookie_names' => array_keys($request->cookies->all()),
                ];
            }

            return response()->json($payload, 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login Dosen berhasil.',
            'data' => [
                'kode_dosen' => $user->kode_dosen ?? $user->id,
                'nik' => $user->nik,
                'nama' => $user->nama_dosen,
                'email' => $user->alamat_email,
                'type' => 'dosen',
            ],
        ]);
    }

    /**
     * Logout — hapus sesi.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Check if this request has a valid session store.
     */

    /**
     * Data user yang sedang login (dari session via Sanctum Cookie).
     * Middleware: auth:sanctum + sanctum.cookie
     */
    public function me_mahasiswa(Request $request): JsonResponse
    {
        // Check authenticated user dari session (mahasiswa_web)
        $user = Auth::guard('mahasiswa_web')->user() ?? Auth::guard('dosen_web')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. User not found.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id ?? null,
                'identifier' => $user->nim ?? null, // NIM untuk mahasiswa
                'nama' => $user->nama_mahasiswa ?? null,
                'email' => $user->email ?? null,
                'type' => 'mahasiswa',
            ],
        ]);
    }

    public function me_dosen(Request $request): JsonResponse
    {
        // Check authenticated user dari session (dosen_web)
        $user = Auth::guard('dosen_web')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. User not found.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id ?? null,
                'identifier' => $user->nik ?? null, // NIK untuk dosen
                'nama' => $user->nama_dosen ?? null,
                'email' => $user->alamat_email ?? null,
                'type' => 'dosen',
            ],
        ]);
    }
}
