<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeederCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class FeederConfigController extends Controller
{
    private function requireAdmin(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengelola Konfigurasi Feeder.');
        }
    }

    /**
     * Tampilkan halaman formulir konfigurasi feeder.
     */
    public function index(): View
    {
        $this->requireAdmin();

        $credentials = FeederCredential::getAllCredentials();
        $records = FeederCredential::with('updater')->get()->keyBy('key_name');
        $wsUrl = FeederCredential::getWsUrl();

        return view('admin.feeder-config.index', compact('credentials', 'records', 'wsUrl'));
    }

    /**
     * Simpan / perbarui konfigurasi feeder.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->requireAdmin();

        $request->validate([
            'feeder_url' => 'required|string|max:255',
            'feeder_port' => 'nullable|numeric|digits_between:1,5',
            'feeder_endpoint' => 'nullable|string|max:100',
            'feeder_username' => 'nullable|string|max:100',
            'feeder_password' => 'nullable|string|max:255',
        ], [
            'feeder_url.required' => 'URL Feeder wajib diisi.',
            'feeder_port.numeric' => 'Port harus berupa angka.',
        ]);

        $url = rtrim(trim($request->input('feeder_url')), '/');
        // Pastikan scheme http:// atau https:// ada
        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'http://'.$url;
        }

        FeederCredential::setValue('feeder_url', $url, 'URL Base Feeder PDDikti');
        FeederCredential::setValue('feeder_port', $request->filled('feeder_port') ? trim($request->input('feeder_port')) : null, 'Port Layanan Feeder');
        
        $endpoint = trim($request->input('feeder_endpoint', 'ws/live2.php') ?: 'ws/live2.php');
        FeederCredential::setValue('feeder_endpoint', ltrim($endpoint, '/'), 'Endpoint Web Service Feeder');
        
        FeederCredential::setValue('feeder_username', $request->input('feeder_username'), 'Username / Kode PT Feeder');

        // Jika password diisi, perbarui. Jika dikosongkan, pertahankan password lama
        if ($request->filled('feeder_password')) {
            FeederCredential::setValue('feeder_password', $request->input('feeder_password'), 'Password Akun Feeder');
        }

        return redirect()->route('admin.feeder-config.index')
            ->with('success', 'Konfigurasi Feeder berhasil disimpan.');
    }

    /**
     * Uji koneksi ke web service Feeder PDDikti (GetToken).
     */
    public function testConnection(Request $request): RedirectResponse|JsonResponse
    {
        $this->requireAdmin();

        // Ambil konfigurasi tersimpan atau dari input jika disertakan
        $url = $request->input('feeder_url') ?: FeederCredential::getValue('feeder_url');
        $port = $request->input('feeder_port') ?: FeederCredential::getValue('feeder_port');
        $endpoint = $request->input('feeder_endpoint') ?: FeederCredential::getValue('feeder_endpoint', 'ws/live2.php');
        $username = $request->input('feeder_username') ?: FeederCredential::getValue('feeder_username');
        $password = $request->input('feeder_password') ?: FeederCredential::getValue('feeder_password');

        if (! $url) {
            $msg = 'URL Feeder belum dikonfigurasi.';
            return $request->wantsJson()
                ? response()->json(['status' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        if (! $username || ! $password) {
            $msg = 'Username dan Password Feeder wajib diisi untuk melakukan uji koneksi.';
            return $request->wantsJson()
                ? response()->json(['status' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        // Susun URL Web Service
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? $url;
        $targetPort = $port ?: ($parsed['port'] ?? null);
        $portSuffix = $targetPort ? ":{$targetPort}" : '';
        $cleanEndpoint = ltrim($endpoint ?: 'ws/live2.php', '/');
        $wsUrl = "{$scheme}://{$host}{$portSuffix}/{$cleanEndpoint}";

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->post($wsUrl, [
                    'act' => 'GetToken',
                    'username' => $username,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $body = $response->json();

                // Format respon standar Neo Feeder PDDikti
                $errorCode = $body['error_code'] ?? null;
                $errorDesc = $body['error_desc'] ?? null;
                $token = $body['data']['token'] ?? null;

                if ($errorCode === 0 && ! empty($token)) {
                    // Simpan token baru ke database
                    FeederCredential::setValue('feeder_token', $token, 'Token aktif hasil pengujian koneksi');

                    $successMsg = "Koneksi berhasil! Token Feeder didapatkan: {$token}";

                    return $request->wantsJson()
                        ? response()->json(['status' => true, 'message' => $successMsg, 'token' => $token])
                        : back()->with('success', $successMsg);
                }

                $failMsg = 'Koneksi ditolak oleh Feeder: '.($errorDesc ?: 'Kode error '.$errorCode);

                return $request->wantsJson()
                    ? response()->json(['status' => false, 'message' => $failMsg], 400)
                    : back()->with('error', $failMsg);
            }

            $httpError = "Gagal terhubung ke Feeder (HTTP {$response->status()}). Periksa URL dan Port Feeder.";

            return $request->wantsJson()
                ? response()->json(['status' => false, 'message' => $httpError], 400)
                : back()->with('error', $httpError);

        } catch (Throwable $e) {
            $exMsg = 'Terjadi kesalahan koneksi: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['status' => false, 'message' => $exMsg], 500)
                : back()->with('error', $exMsg);
        }
    }
}
