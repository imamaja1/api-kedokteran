<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\KelasMahasiswa;
use App\Models\KrsDetail;
use App\Models\Mengajar;
use App\Models\Matakuliah;
use App\Models\NamaKelas;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $query = Kelas::with(['namaKelas', 'matakuliah']);

        if ($namaKelasId = $request->query('nama_kelas_id')) {
            $query->where('nama_kelas_id', $namaKelasId);
        }

        if ($semester = $request->query('semester')) {
            $query->where('semester', $semester);
        }

        if ($ta = $request->query('kode_tahun_akademik')) {
            $query->where('kode_tahun_akademik', $ta);
        }

        if ($search = $request->query('search')) {
            $query->whereHas('matakuliah', function ($q) use ($search) {
                $q->where('nama_matakuliah', 'like', "%{$search}%")
                  ->orWhere('kode_matakuliah', 'like', "%{$search}%");
            });
        }

        $kelasList    = $query->orderBy('nama_kelas_id')->orderBy('semester')->paginate(20)->withQueryString();
        $namaKelasList = NamaKelas::orderBy('nama_kelas')->get();
        $tahunAkademiks = TahunAkademik::orderByDesc('kode_tahun_akademik')->get();

        return view('admin.kelas.index', compact('kelasList', 'namaKelasList', 'tahunAkademiks'));
    }

    public function show(int $id)
    {
        $kelas = Kelas::with([
            'namaKelas',
            'matakuliah',
            'kelasMahasiswa.krsDetail.krs.mahasiswa',
            'mengajar.dosen',
        ])->findOrFail($id);

        // All KRS details for the same matakuliah (for mahasiswa dropdown)
        $krsDetails = KrsDetail::with('krs.mahasiswa')
            ->where('id_matakuliah', $kelas->id_matakuliah)
            ->get();

        // Already assigned kode_krs_detail IDs
        $assignedKrsDetailIds = $kelas->kelasMahasiswa->pluck('kode_krs_detail')->toArray();

        // Available KRS details (not yet in this kelas)
        $availableKrsDetails = $krsDetails->whereNotIn('kode_krs_detail', $assignedKrsDetailIds)->values();

        // All dosens
        $dosens = Dosen::where('aktif', 'A')->orderBy('nama_dosen')->get();

        // Already assigned kode_dosen IDs
        $assignedDosenIds = $kelas->mengajar->pluck('kode_dosen')->toArray();

        $availableDosens = $dosens->whereNotIn('kode_dosen', $assignedDosenIds)->values();

        return view('admin.kelas.show', compact(
            'kelas', 'availableKrsDetails', 'availableDosens', 'assignedDosenIds'
        ));
    }

    public function storeMahasiswa(Request $request, int $id)
    {
        $kelas = Kelas::findOrFail($id);

        $request->validate([
            'kode_krs_detail' => 'required|integer|exists:krs_detail,kode_krs_detail',
        ]);

        $kode = $request->integer('kode_krs_detail');

        // Prevent duplicate
        $exists = KelasMahasiswa::where('kelas_id', $kelas->kelas_id)
            ->where('kode_krs_detail', $kode)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Mahasiswa tersebut sudah ada di kelas ini.');
        }

        KelasMahasiswa::create([
            'kelas_id'        => $kelas->kelas_id,
            'kode_krs_detail' => $kode,
        ]);

        return back()->with('success', 'Mahasiswa berhasil ditambahkan ke kelas.');
    }

    public function destroyMahasiswa(int $id, int $kmId)
    {
        $km = KelasMahasiswa::where('kelas_id', $id)->where('kelas_mahasiswa_id', $kmId)->firstOrFail();
        $km->delete();

        return back()->with('success', 'Mahasiswa berhasil dihapus dari kelas.');
    }

    public function storeDosen(Request $request, int $id)
    {
        $kelas = Kelas::findOrFail($id);

        $request->validate([
            'kode_dosen' => 'required|integer|exists:dosen,kode_dosen',
        ]);

        $kodeDosen = $request->integer('kode_dosen');

        // Prevent duplicate
        $exists = Mengajar::where('kelas_id', $kelas->kelas_id)
            ->where('kode_dosen', $kodeDosen)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Dosen tersebut sudah mengajar di kelas ini.');
        }

        Mengajar::create([
            'kelas_id'   => $kelas->kelas_id,
            'kode_dosen' => $kodeDosen,
        ]);

        return back()->with('success', 'Dosen berhasil ditambahkan ke kelas.');
    }

    public function destroyDosen(int $id, int $mengajarId)
    {
        $m = Mengajar::where('kelas_id', $id)->where('mengajar_id', $mengajarId)->firstOrFail();
        $m->delete();

        return back()->with('success', 'Dosen berhasil dihapus dari kelas.');
    }

    public function syncWithSiska()
    {
        // 1. Ambil 3 koneksi SISKA berdasarkan nama
        $csrfConn = ApiConnection::where('is_active', true)->where('name', 'CSRF Cookie')->first();
        $credConn = ApiConnection::where('is_active', true)->where('name', 'Credential Api Siska')->first();
        $dataConn = ApiConnection::where('is_active', true)->where('name', 'Get Mhs Kedokteran API-SISKA')->first();
    }
}
