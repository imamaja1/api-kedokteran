<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DosenLoginRequest;
use App\Http\Requests\Auth\MhsLoginRequest;
use App\Http\Requests\Auth\StaffLoginRequest;
use App\Models\ActivityLog;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function mhs_login(MhsLoginRequest $request): JsonResponse
    {
        $nim = $request->nim;
        $password = $request->password;

        $user = Mahasiswa::where('nim', $nim)
            ->orWhere('email', $nim)
            ->first();

        if (! $user || ! Hash::check($password, $user->sandi)) {
            $this->writeLog($request, 'mahasiswa', $nim, 401);
            return response()->json([
                'status' => false,
                'message' => 'NIM atau password salah.',
            ], 401);
        }

        // Authenticate dengan guard mahasiswa_web
        Auth::guard('mahasiswa_web')->login($user);
        $request->session()->regenerate();

        $this->writeLog($request, 'mahasiswa', $user->nim, 200);

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

        $user = Dosen::where('alamat_email', $email)
            ->first();
        
        if (! $user || ! Hash::check($password, $user->sandi_pengguna)) {
            $this->writeLog($request, 'dosen', $email, 401);
            return response()->json([
                'status' => false,
                'message' => 'Kode Dosen atau password salah.',
            ], 401);
        }

        // Authenticate dengan guard dosen_web
        Auth::guard('dosen_web')->login($user);
        $request->session()->regenerate();

        $this->writeLog($request, 'dosen', $user->kode_dosen ?? (string) $user->id, 200);

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

    public function login_staff(StaffLoginRequest $request): JsonResponse
    {
        $email = $request->email;
        $password = $request->password;

        // Optimized: Select only needed columns
        $user = User::where('email', $email)
            ->where('role', 'staff')
            ->select(['id', 'name', 'email', 'password', 'role'])
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            // Optimized: Only write log on failure (less frequent)
            $this->writeLog($request, 'staff', $email, 401);
            return response()->json([
                'status' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Authenticate dengan guard staff_web
        Auth::guard('staff_web')->login($user);
        
        // Optimized: Only regenerate session once (not on every login)
        if (!$request->session()->isStarted()) {
            $request->session()->start();
        } else {
            $request->session()->migrate();
        }

        // Optimized: Defer logging to after response (async)
        $this->writeLogAsync($request, 'staff', (string) $user->id, 200);

        return response()->json([
            'status' => true,
            'message' => 'Login Staff berhasil.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'nama' => $user->name,
                'type' => 'staff',
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function logout(Request $request): JsonResponse
    {
        // Catat logout sebelum session di-invalidate
        foreach (['mahasiswa_web', 'dosen_web', 'staff_web'] as $guard) {
            if (! Auth::guard($guard)->check()) {
                continue;
            }
            $user = Auth::guard($guard)->user();
            [$userType, $userId] = match ($guard) {
                'mahasiswa_web' => ['mahasiswa', $user->nim ?? null],
                'dosen_web'     => ['dosen',     $user->kode_dosen ?? null],
                'staff_web'     => ['staff',     (string) ($user->id ?? null)],
                default         => [null, null],
            };
            $this->writeLog($request, $userType, $userId, 200);
            Auth::guard($guard)->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me_mahasiswa(Request $request): JsonResponse
    {
        $user = Auth::guard('mahasiswa_web')->user();

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

    public function me_staff(Request $request): JsonResponse
    {
        // Check authenticated user dari session (staff_web)
        $user = Auth::guard('staff_web')->user() ?? $request->user();

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
                'email' => $user->email ?? null,
                'nama' => $user->name ?? null,
                'type' => 'staff',
            ],
        ]);
    }

    private function writeLog(Request $request, ?string $userType, ?string $userId, int $statusCode): void
    {
        try {
            ActivityLog::create([
                'guard'       => $userType ? $userType . '_web' : null,
                'user_id'     => $userId,
                'user_type'   => $userType,
                'method'      => $request->method(),
                'path'        => $request->path(),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'status_code' => $statusCode,
            ]);
        } catch (\Throwable) {
            // Kegagalan logging tidak boleh menghentikan response
        }
    }

    /**
     * Optimized: Async-like logging using register_shutdown_function
     * This defers the logging until after the response is sent
     */
    private function writeLogAsync(Request $request, ?string $userType, ?string $userId, int $statusCode): void
    {
        // Capture data needed for logging
        $logData = [
            'guard'       => $userType ? $userType . '_web' : null,
            'user_id'     => $userId,
            'user_type'   => $userType,
            'method'      => $request->method(),
            'path'        => $request->path(),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'status_code' => $statusCode,
        ];

        // Register shutdown function to log after response
        register_shutdown_function(function () use ($logData) {
            try {
                // Fast CGI finish if available
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                
                ActivityLog::create($logData);
            } catch (\Throwable) {
                // Logging failure should not affect anything
            }
        });
    }
}
