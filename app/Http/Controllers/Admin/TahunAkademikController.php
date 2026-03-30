<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;

class TahunAkademikController extends Controller
{
    public function index()
    {
        $tahunAkademiks = TahunAkademik::orderByDesc('kode_tahun_akademik')->get();
        return view('admin.tahun-akademik.index', compact('tahunAkademiks'));
    }

    public function create()
    {
        return view('admin.tahun-akademik.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tahun_akademik'   => 'required|string|max:9|regex:/^\d{4}\/\d{4}$/',
            'semester'         => 'required|in:1,2',
            'tanggal_mulai'    => 'required|date',
            'tanggal_berakhir' => 'required|date|after:tanggal_mulai',
            'status'           => 'required|in:A,N',
            'status_kpat'      => 'nullable|in:A,N',
        ]);

        // Jika status baru = Aktif, nonaktifkan yang lain pada semester yang sama
        if ($data['status'] === 'A') {
            TahunAkademik::where('semester', $data['semester'])
                ->where('status', 'A')
                ->update(['status' => 'N']);
        }

        TahunAkademik::create($data);

        return redirect()->route('admin.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil ditambahkan.');
    }

    public function edit(TahunAkademik $tahunAkademik)
    {
        return view('admin.tahun-akademik.edit', compact('tahunAkademik'));
    }

    public function update(Request $request, TahunAkademik $tahunAkademik)
    {
        $data = $request->validate([
            'tahun_akademik'   => 'required|string|max:9|regex:/^\d{4}\/\d{4}$/',
            'semester'         => 'required|in:1,2',
            'tanggal_mulai'    => 'required|date',
            'tanggal_berakhir' => 'required|date|after:tanggal_mulai',
            'status'           => 'required|in:A,N',
            'status_kpat'      => 'nullable|in:A,N',
        ]);

        // Jika di-set Aktif, nonaktifkan yang lain (selain record ini)
        if ($data['status'] === 'A') {
            TahunAkademik::where('semester', $data['semester'])
                ->where('status', 'A')
                ->where('kode_tahun_akademik', '!=', $tahunAkademik->kode_tahun_akademik)
                ->update(['status' => 'N']);
        }

        $tahunAkademik->update($data);

        return redirect()->route('admin.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil diperbarui.');
    }

    public function destroy(TahunAkademik $tahunAkademik)
    {
        $tahunAkademik->delete();

        return redirect()->route('admin.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil dihapus.');
    }
}
