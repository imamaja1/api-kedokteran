<?php

namespace App\Service;

use App\Http\Responses\ApiResponse;
use App\Models\Dosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ServiceDosen
{
    public function getAllDosen(
        ?string $kode_program_studi = null,
        ?string $nama_dosen = null,
        ?string $alamat_email = null,
    ): JsonResponse {
        $query = Dosen::query()
            ->with("programStudi")
            ->when($kode_program_studi, function ($query, $kode) {
                return $query->where("kode_program_studi", $kode);
            })
            ->when($nama_dosen, function ($query, $nama) {
                return $query->where("nama_dosen", "like", "%{$nama}%");
            })
            ->when($alamat_email, function ($query, $email) {
                return $query->where("alamat_email", "like", "%{$email}%");
            });

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return $this->formatDosen($item, $index + 1);
        });

        return response()->json([
            "status" => true,
            "message" => "API Dosen",
            "jumlah" => $paginator->total(),
            "data" => $paginator->items(),
            "pagination" => [
                "current_page" => $paginator->currentPage(),
                "per_page" => $paginator->perPage(),
                "last_page" => $paginator->lastPage(),
                "from" => $paginator->firstItem(),
                "to" => $paginator->lastItem(),
            ],
        ]);
    }

    public function getOneDosen(string $id): JsonResponse
    {
        $dosen = Dosen::with("programStudi")->where("kode_dosen", $id)->first();

        if (!$dosen) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Dosen tidak ditemukan",
                    "data" => null,
                ],
                404,
            );
        }

        return response()->json([
            "status" => true,
            "message" => "API Dosen",
            "data" => $this->formatDosen($dosen),
        ]);
    }

    public function storeDosen(array $object): JsonResponse
    {
        if (!empty($object["sandi_pengguna"])) {
            $object["sandi_pengguna"] = Hash::make($object["sandi_pengguna"]);
        } else {
            unset($object["sandi_pengguna"]);
        }

        $object["chatid"] = $object["chatid"] ?? "";
        $object["status_login"] = "N";

        try {
            $dosen = Dosen::create($object);
        } catch (\Throwable) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Gagal membuat Dosen",
                    "data" => null,
                ],
                500,
            );
        }

        return response()->json(
            [
                "status" => true,
                "message" => "Dosen berhasil dibuat",
                "data" => [
                    "code" => $dosen->toCode(),
                    "nama_dosen" => $dosen->nama_dosen,
                ],
            ],
            201,
        );
    }

    public function updateDosen(string $id, array $object): JsonResponse
    {
        $dosen = Dosen::where("kode_dosen", $id)->first();

        if (!$dosen) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Dosen tidak ditemukan",
                    "data" => null,
                ],
                404,
            );
        }

        if (!empty($object["sandi_pengguna"])) {
            $object["sandi_pengguna"] = Hash::make($object["sandi_pengguna"]);
        } else {
            unset($object["sandi_pengguna"]);
        }

        try {
            $dosen->update($object);
        } catch (\Throwable) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Gagal memperbarui Dosen",
                    "data" => null,
                ],
                500,
            );
        }

        return response()->json([
            "status" => true,
            "message" => "Dosen berhasil diperbarui",
            "data" => [
                "code" => $dosen->toCode(),
                "nama_dosen" => $dosen->nama_dosen,
            ],
        ]);
    }

    public function deleteDosen(string $id): JsonResponse
    {
        $dosen = Dosen::where("kode_dosen", $id)->first();

        if (!$dosen) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Dosen tidak ditemukan",
                    "data" => null,
                ],
                404,
            );
        }

        try {
            $dosen->delete();
        } catch (\Throwable) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Gagal menghapus Dosen",
                    "data" => null,
                ],
                500,
            );
        }

        return response()->json([
            "status" => true,
            "message" => "Dosen berhasil dihapus",
            "data" => [
                "code" => $dosen->toCode(),
                "nama_dosen" => $dosen->nama_dosen,
            ],
        ]);
    }

    public function getDosenTrash(
        ?string $kode_program_studi = null,
        ?string $nama_dosen = null,
        ?string $alamat_email = null,
    ): JsonResponse {
        $query = Dosen::onlyTrashed()
            ->with("programStudi")
            ->when($kode_program_studi, function ($query, $kode) {
                return $query->where("kode_program_studi", $kode);
            })
            ->when($nama_dosen, function ($query, $nama) {
                return $query->where("nama_dosen", "like", "%{$nama}%");
            })
            ->when($alamat_email, function ($query, $email) {
                return $query->where("alamat_email", "like", "%{$email}%");
            });

        $paginator = $query->paginate(20);

        $paginator->getCollection()->transform(function ($item, $index) {
            return $this->formatDosenTrash($item, $index + 1);
        });

        // Return 200 dengan empty data jika kosong (bukan 404 untuk collection queries)
        return ApiResponse::paginated($paginator, "Data Dosen (Trash)");
    }

    public function restoreDosen(string $id): JsonResponse
    {
        $dosen = Dosen::onlyTrashed()->where("kode_dosen", $id)->first();

        if (!$dosen) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Dosen tidak ditemukan di trash",
                    "data" => null,
                ],
                404,
            );
        }

        try {
            $dosen->restore();
        } catch (\Throwable) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Gagal memulihkan Dosen",
                    "data" => null,
                ],
                500,
            );
        }

        return response()->json([
            "status" => true,
            "message" => "Dosen berhasil dipulihkan",
            "data" => [
                "code" => $dosen->toCode(),
                "nama_dosen" => $dosen->nama_dosen,
            ],
        ]);
    }

    public function forceDeleteDosen(string $id): JsonResponse
    {
        $dosen = Dosen::onlyTrashed()->where("kode_dosen", $id)->first();

        if (!$dosen) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Dosen tidak ditemukan di trash",
                    "data" => null,
                ],
                404,
            );
        }

        try {
            $dosen->forceDelete();
        } catch (\Throwable) {
            return response()->json(
                [
                    "status" => false,
                    "message" => "Gagal menghapus permanen Dosen",
                    "data" => null,
                ],
                500,
            );
        }

        return response()->json([
            "status" => true,
            "message" => "Dosen berhasil dihapus permanen",
            "data" => [
                "code" => $dosen->toCode(),
                "nama_dosen" => $dosen->nama_dosen,
            ],
        ]);
    }

    private function formatDosen(Dosen $item, ?int $index = null): array
    {
        $data = [
            "code" => $item->toCode(),
            "nama_dosen" => $item->nama_dosen,
            "nik" => $item->nik,
            "no_telp" => $item->no_telp,
            "alamat_email" => $item->alamat_email,
            "field_studi" => $item->field_studi,
            "alumni" => $item->alumni,
            "homebase" => $item->programStudi?->nama_program_studi,
            "status_dosen" => $item->status_dosen,
            "aktif" => $item->aktif,
            "status_login" => $item->status_login,
            "signature" => $item->signature,
        ];

        if ($index !== null) {
            $data = ["id" => $index] + $data;
        }

        return $data;
    }

    private function formatDosenTrash(Dosen $item, ?int $index = null): array
    {
        $data = $this->formatDosen($item, $index);
        $data["deleted_at"] = $item->deleted_at;

        return $data;
    }
}
