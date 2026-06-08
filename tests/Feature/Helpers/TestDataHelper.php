<?php

namespace Tests\Feature\Helpers;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class TestDataHelper
{
    public static function createProgramStudi(array $overrides = []): ProgramStudi
    {
        return ProgramStudi::create(array_merge([
            'nama_program_studi' => 'Kedokteran',
            'singkatan_program_studi' => 'FK',
        ], $overrides));
    }

    public static function createMahasiswa(array $overrides = []): Mahasiswa
    {
        $prodi = ProgramStudi::first() ?? self::createProgramStudi();
        $count = Mahasiswa::count() + 1;
        $nim = $overrides['nim'] ?? '2023010'.str_pad($count, 4, '0', STR_PAD_LEFT);

        return Mahasiswa::create(array_merge([
            'nim' => $nim,
            'nik' => $overrides['nik'] ?? '12345678901234567'.str_pad($count, 3, '0', STR_PAD_LEFT),
            'npm' => $overrides['npm'] ?? $nim,
            'nomor_pendaftaran' => $overrides['nomor_pendaftaran'] ?? 'REG'.str_pad($count, 3, '0', STR_PAD_LEFT),
            'nomor_pendaftaran_ulang' => $overrides['nomor_pendaftaran_ulang'] ?? 'REG'.str_pad($count, 3, '0', STR_PAD_LEFT),
            'program_studi_kode' => $prodi->kode_program_studi,
            'nama_mahasiswa' => $overrides['nama_mahasiswa'] ?? "Mahasiswa Test {$count}",
            'email' => $overrides['email'] ?? "mhs{$count}@test.com",
            'sandi' => Hash::make('password'),
            'status' => 'A',
            'status_pendaftaran' => 'L',
        ], $overrides));
    }

    public static function createDosen(array $overrides = []): Dosen
    {
        $prodi = ProgramStudi::first() ?? self::createProgramStudi();
        $count = Dosen::count() + 1;

        return Dosen::create(array_merge([
            'kode_dosen' => (Dosen::max('kode_dosen') ?? 0) + 1,
            'nama_dosen' => $overrides['nama_dosen'] ?? "Dosen Test {$count}",
            'nik' => $overrides['nik'] ?? '1234567'.str_pad($count, 3, '0', STR_PAD_LEFT),
            'no_telp' => $overrides['no_telp'] ?? '0812345678'.str_pad($count, 2, '0', STR_PAD_LEFT),
            'alamat_email' => $overrides['alamat_email'] ?? "dosen{$count}@test.com",
            'field_studi' => 'Kedokteran',
            'alumni' => '-',
            'homebase' => $prodi->kode_program_studi,
            'status_dosen' => 'T',
            'aktif' => 'A',
            'chatid' => str_pad($count, 5, '0', STR_PAD_LEFT),
            'sandi_pengguna' => Hash::make('password'),
        ], $overrides));
    }

    public static function createStaff(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Staff Test',
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ], $overrides));
    }

    public static function createTahunAkademik(array $overrides = []): TahunAkademik
    {
        return TahunAkademik::create(array_merge([
            'tahun_akademik' => '2024/2025',
            'semester' => '1',
            'tanggal_mulai' => '2024-09-01',
            'tanggal_berakhir' => '2025-01-15',
            'status' => 'A',
        ], $overrides));
    }

    public static function encryptCode(mixed $id): string
    {
        return Crypt::encryptString((string) $id);
    }

    public static function getDosenCode(Dosen $dosen): string
    {
        return $dosen->toCode();
    }

    public static function getMahasiswaCode(Mahasiswa $mhs): string
    {
        return $mhs->toCode();
    }
}
