<?php

namespace App\Service;

use App\Models\TahunAkademik;
use Illuminate\Support\Facades\Crypt;

class ServiceTahunAkademik
{
  public function __construct()
  {
    //
  }

  public function getAllTahunAkademik(array $filters = [])
  {
    $query = TahunAkademik::orderByDesc('kode_tahun_akademik');

    if (!empty($filters['tahun_akademik'])) {
      $query->where('tahun_akademik', $filters['tahun_akademik']);
    }

    if (!empty($filters['semester'])) {
      $query->where('semester', $filters['semester']);
    }

    if (!empty($filters['status'])) {
      $query->where('status', $filters['status']);
    }

    $data = $query->get()
      ->map(function ($item, $nomor) {
        return [
          'id' => $nomor + 1,
          'code' => Crypt::encryptString($item->kode_tahun_akademik),
          'tahun_akademik' => $item->tahun_akademik,
          'semester' => $item->semester,
          'tanggal_mulai' => $item->tanggal_mulai?->format('Y-m-d'),
          'tanggal_berakhir' => $item->tanggal_berakhir?->format('Y-m-d'),
          'status' => $item->status,
          'status_kpat' => $item->status_kpat,
        ];
      });

    return response()->json([
      'status' => true,
      'message' => 'API Tahun Akademik',
      'data' => $data,
    ]);
  }

  public function getOneTahunAkademik($id)
  {
    $data = TahunAkademik::find($id);

    if (!$data) {
      return response()->json([
        'status' => false,
        'message' => 'Tahun akademik tidak ditemukan',
        'data' => null,
      ], 404);
    }

    return response()->json([
      'status' => true,
      'message' => 'API Tahun Akademik',
      'data' => [
        'code' => Crypt::encryptString($data->kode_tahun_akademik),
        'tahun_akademik' => $data->tahun_akademik,
        'semester' => $data->semester,
        'tanggal_mulai' => $data->tanggal_mulai?->format('Y-m-d'),
        'tanggal_berakhir' => $data->tanggal_berakhir?->format('Y-m-d'),
        'status' => $data->status,
        'status_kpat' => $data->status_kpat,
      ],
    ]);
  }

  public function storeTahunAkademik(array $object)
  {
    if (isset($object['status']) && $object['status'] === 'A') {
      TahunAkademik::where('semester', $object['semester'])
        ->where('status', 'A')
        ->update(['status' => 'N']);
    }

    try {
      $tahunAkademik = TahunAkademik::create($object);
    } catch (\Throwable $th) {
      return response()->json([
        'status' => false,
        'message' => 'Gagal membuat Tahun Akademik',
        'data' => null,
      ], 500);
    }

    return response()->json([
      'status' => true,
      'message' => 'Tahun Akademik berhasil dibuat',
      'data' => [
        'code' => Crypt::encryptString($tahunAkademik->kode_tahun_akademik),
        'tahun_akademik' => $tahunAkademik->tahun_akademik,
        'semester' => $tahunAkademik->semester,
        'tanggal_mulai' => $tahunAkademik->tanggal_mulai?->format('Y-m-d'),
        'tanggal_berakhir' => $tahunAkademik->tanggal_berakhir?->format('Y-m-d'),
        'status' => $tahunAkademik->status,
        'status_kpat' => $tahunAkademik->status_kpat,
      ],
    ], 201);
  }

  public function updateTahunAkademik($id, array $object)
  {
    $tahunAkademik = TahunAkademik::find($id);

    if (!$tahunAkademik) {
      return response()->json([
        'status' => false,
        'message' => 'Tahun akademik tidak ditemukan',
        'data' => null,
      ], 404);
    }

    if (isset($object['status']) && $object['status'] === 'A') {
      TahunAkademik::where('semester', $object['semester'])
        ->where('status', 'A')
        ->where('kode_tahun_akademik', '!=', $tahunAkademik->kode_tahun_akademik)
        ->update(['status' => 'N']);
    }

    try {
      $tahunAkademik->update($object);
    } catch (\Throwable $th) {
      return response()->json([
        'status' => false,
        'message' => 'Gagal memperbarui Tahun Akademik',
        'data' => null,
      ], 500);
    }

    return response()->json([
      'status' => true,
      'message' => 'Tahun Akademik berhasil diperbarui',
      'data' => [
        'code' => Crypt::encryptString($tahunAkademik->kode_tahun_akademik),
        'tahun_akademik' => $tahunAkademik->tahun_akademik,
        'semester' => $tahunAkademik->semester,
        'tanggal_mulai' => $tahunAkademik->tanggal_mulai?->format('Y-m-d'),
        'tanggal_berakhir' => $tahunAkademik->tanggal_berakhir?->format('Y-m-d'),
        'status' => $tahunAkademik->status,
        'status_kpat' => $tahunAkademik->status_kpat,
      ],
    ]);
  }

  public function deleteTahunAkademik($id)
  {
    $tahunAkademik = TahunAkademik::find($id);

    if (!$tahunAkademik) {
      return response()->json([
        'status' => false,
        'message' => 'Tahun akademik tidak ditemukan',
        'data' => null,
      ], 404);
    }

    try {
      $tahunAkademik->delete();
    } catch (\Throwable $th) {
      return response()->json([
        'status' => false,
        'message' => 'Gagal menghapus Tahun Akademik',
        'data' => null,
      ], 500);
    }

    return response()->json([
      'status' => true,
      'message' => 'Tahun Akademik berhasil dihapus',
      'data' => [
        'code' => Crypt::encryptString($tahunAkademik->kode_tahun_akademik),
        'tahun_akademik' => $tahunAkademik->tahun_akademik,
        'semester' => $tahunAkademik->semester,
        'tanggal_mulai' => $tahunAkademik->tanggal_mulai?->format('Y-m-d'),
        'tanggal_berakhir' => $tahunAkademik->tanggal_berakhir?->format('Y-m-d'),
        'status' => $tahunAkademik->status,
        'status_kpat' => $tahunAkademik->status_kpat,
      ],
    ]);
  }
}
