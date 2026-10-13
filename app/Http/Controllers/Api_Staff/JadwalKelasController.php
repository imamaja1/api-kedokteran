<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Service\ServiceJadwalKelas;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class JadwalKelasController extends Controller
{
    public function __construct(
        private readonly ServiceJadwalKelas $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Resolusi filter terenkripsi jika dikirim
        $resolved = $this->resolveEncryptedFilters($payload);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $validator = Validator::make($resolved, [
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'kelas_id' => ['nullable', 'integer'],
            'ruangan_id' => ['nullable', 'integer'],
            'hari_id' => ['nullable', 'integer'],
            'semester' => ['nullable', 'string', 'max:2'],
            'id_matakuliah' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        return $this->service->index($validator->validated());
    }

    public function statusKelas(Request $request): JsonResponse
    {
        $payload = $request->all();
        if (! empty($payload['code_tahun_akademik'])) {
            $decryptedTa = $this->decryptInteger($payload['code_tahun_akademik'], 'code_tahun_akademik');
            if ($decryptedTa instanceof JsonResponse) {
                return $decryptedTa;
            }
            $payload['kode_tahun_akademik'] = $decryptedTa;
        }

        $validator = Validator::make($payload, [
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'semester' => ['nullable', 'string', 'max:2'],
            'kode_program_studi' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'in:belum_terjadwal,sudah_terjadwal'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        return $this->service->statusKelas($validator->validated());
    }

    public function getByKelas(Request $request, ?string $code = null): JsonResponse
    {
        $rawCode = $code ?? $request->query('code') ?? $request->query('code_kelas') ?? $request->input('code') ?? $request->input('code_kelas');
        $resolvedKelasId = $this->resolveId($rawCode, 'code_kelas');
        if ($resolvedKelasId instanceof JsonResponse) {
            return $resolvedKelasId;
        }

        return $this->service->getByKelas($resolvedKelasId);
    }

    public function getByRuangan(Request $request, ?string $code = null): JsonResponse
    {
        $rawCode = $code ?? $request->query('code') ?? $request->query('code_ruangan') ?? $request->input('code') ?? $request->input('code_ruangan');
        $resolvedRuanganId = $this->resolveId($rawCode, 'code_ruangan');
        if ($resolvedRuanganId instanceof JsonResponse) {
            return $resolvedRuanganId;
        }

        $payload = $request->all();
        $resolved = $this->resolveEncryptedFilters($payload);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $validator = Validator::make($resolved, [
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'hari_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        return $this->service->getByRuangan($resolvedRuanganId, $validator->validated());
    }

    public function getByHari(Request $request, ?string $code = null): JsonResponse
    {
        $rawCode = $code ?? $request->query('code') ?? $request->query('code_hari') ?? $request->input('code') ?? $request->input('code_hari');
        $resolvedHariId = $this->resolveId($rawCode, 'code_hari');
        if ($resolvedHariId instanceof JsonResponse) {
            return $resolvedHariId;
        }

        $payload = $request->all();
        $resolved = $this->resolveEncryptedFilters($payload);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $validator = Validator::make($resolved, [
            'kode_tahun_akademik' => ['nullable', 'integer'],
            'ruangan_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        return $this->service->getByHari($resolvedHariId, $validator->validated());
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Resolusi code_* ke id integer jika dikirim
        $resolved = $this->resolvePayloadIds($payload);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $validator = Validator::make($resolved, [
            'kelas_id' => ['required', 'integer', 'exists:kelas,kelas_id'],
            'ruangan_id' => ['required', 'integer', 'exists:ruangan,id'],
            'hari_id' => ['required', 'integer', 'exists:hari,id'],
            'jam_mulai' => ['required', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'jam_selesai' => ['required', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'kode_tahun_akademik' => ['nullable', 'integer', 'exists:tahun_akademik,kode_tahun_akademik'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kelas_id.required' => 'Kelas kuliah (code_kelas) wajib diisi.',
            'kelas_id.exists' => 'Kelas kuliah yang dipilih tidak ditemukan.',
            'ruangan_id.required' => 'Ruangan (code_ruangan) wajib diisi.',
            'ruangan_id.exists' => 'Ruangan yang dipilih tidak ditemukan.',
            'hari_id.required' => 'Hari (code_hari) wajib diisi.',
            'hari_id.exists' => 'Hari yang dipilih tidak ditemukan.',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        return $this->service->store($validator->validated());
    }

    public function show(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->query('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        return $this->service->show($resolvedId);
    }

    public function update(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        $payload = $request->all();
        $resolved = $this->resolvePayloadIds($payload);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $validator = Validator::make($resolved, [
            'ruangan_id' => ['sometimes', 'integer', 'exists:ruangan,id'],
            'hari_id' => ['sometimes', 'integer', 'exists:hari,id'],
            'jam_mulai' => ['sometimes', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'jam_selesai' => ['sometimes', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'kode_tahun_akademik' => ['nullable', 'integer', 'exists:tahun_akademik,kode_tahun_akademik'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        return $this->service->update($resolvedId, $validator->validated());
    }

    public function destroy(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code') ?? $request->query('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        return $this->service->destroy($resolvedId);
    }

    public function copy(Request $request, ?string $code = null): JsonResponse
    {
        $resolvedId = $this->resolveId($code ?? $request->input('code') ?? $request->query('code'), 'code');
        if ($resolvedId instanceof JsonResponse) {
            return $resolvedId;
        }

        $payload = $request->all();
        $resolved = $this->resolvePayloadIds($payload);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $validator = Validator::make($resolved, [
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,kelas_id'],
            'ruangan_id' => ['nullable', 'integer', 'exists:ruangan,id'],
            'hari_id' => ['nullable', 'integer', 'exists:hari,id'],
            'jam_mulai' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'jam_selesai' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'kode_tahun_akademik' => ['nullable', 'integer', 'exists:tahun_akademik,kode_tahun_akademik'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        return $this->service->copy($resolvedId, $validator->validated());
    }

    /**
     * Helper resolusi field ID yang bisa dikirim dalam bentuk code_* terenkripsi maupun integer mentah.
     */
    private function resolvePayloadIds(array $payload): array|JsonResponse
    {
        $resolved = $payload;

        if (! empty($payload['code_kelas'])) {
            $decrypted = $this->decryptInteger($payload['code_kelas'], 'code_kelas');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['kelas_id'] = $decrypted;
        }

        if (! empty($payload['code_ruangan'])) {
            $decrypted = $this->decryptInteger($payload['code_ruangan'], 'code_ruangan');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['ruangan_id'] = $decrypted;
        }

        if (! empty($payload['code_hari'])) {
            $decrypted = $this->decryptInteger($payload['code_hari'], 'code_hari');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['hari_id'] = $decrypted;
        }

        if (! empty($payload['code_tahun_akademik'])) {
            $decrypted = $this->decryptInteger($payload['code_tahun_akademik'], 'code_tahun_akademik');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['kode_tahun_akademik'] = $decrypted;
        }

        return $resolved;
    }

    /**
     * Helper resolusi filter query params.
     */
    private function resolveEncryptedFilters(array $filters): array|JsonResponse
    {
        $resolved = $filters;

        if (! empty($filters['code_kelas'])) {
            $decrypted = $this->decryptInteger($filters['code_kelas'], 'code_kelas');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['kelas_id'] = $decrypted;
        }

        if (! empty($filters['code_ruangan'])) {
            $decrypted = $this->decryptInteger($filters['code_ruangan'], 'code_ruangan');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['ruangan_id'] = $decrypted;
        }

        if (! empty($filters['code_hari'])) {
            $decrypted = $this->decryptInteger($filters['code_hari'], 'code_hari');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['hari_id'] = $decrypted;
        }

        if (! empty($filters['code_matakuliah'])) {
            $decrypted = $this->decryptInteger($filters['code_matakuliah'], 'code_matakuliah');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['id_matakuliah'] = $decrypted;
        }

        if (! empty($filters['code_tahun_akademik'])) {
            $decrypted = $this->decryptInteger($filters['code_tahun_akademik'], 'code_tahun_akademik');
            if ($decrypted instanceof JsonResponse) return $decrypted;
            $resolved['kode_tahun_akademik'] = $decrypted;
        }

        return $resolved;
    }

    private function resolveId(mixed $value, string $field = 'code'): int|JsonResponse
    {
        if ($value === null || $value === '') {
            return ApiResponse::validation([$field => "Parameter {$field} wajib diisi."]);
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $this->decryptInteger((string) $value, $field);
    }

    private function decryptInteger(string $value, string $field): int|JsonResponse
    {
        try {
            return (int) Crypt::decryptString($value);
        } catch (DecryptException) {
            return ApiResponse::validation([$field => "Format {$field} tidak valid."]);
        }
    }
}
