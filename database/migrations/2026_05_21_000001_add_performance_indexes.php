<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper to safely add index by checking if exists first
     */
    private function safeAddIndex($table, $columns, $indexName)
    {
        // Check if index already exists
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return; // Index already exists
                }
            }

            // Add the index
            $columnList = is_array($columns) ? implode(',', $columns) : $columns;
            DB::statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$columnList})");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========== MAHASISWA TABLE ==========
        $this->safeAddIndex('mahasiswa', 'program_studi_kode', 'idx_program_studi_kode');
        $this->safeAddIndex('mahasiswa', 'email', 'idx_email');

        // ========== KRS TABLE ==========
        $this->safeAddIndex('krs', 'nim', 'idx_krs_nim');
        $this->safeAddIndex('krs', 'kode_tahun_akademik', 'idx_kode_tahun_akademik');
        $this->safeAddIndex('krs', ['nim', 'semester'], 'idx_nim_semester');

        // ========== KRS_DETAIL TABLE ==========
        $this->safeAddIndex('krs_detail', 'kode_krs', 'idx_kode_krs');
        $this->safeAddIndex('krs_detail', 'id_matakuliah', 'idx_id_matakuliah');

        // ========== KHS_DETAIL TABLE ==========
        $this->safeAddIndex('khs_detail', 'kode_krs_detail', 'idx_kode_krs_detail');

        // ========== MATAKULIAH TABLE ==========
        $this->safeAddIndex('matakuliah', 'kode_program_studi', 'idx_matakuliah_prodi');
        $this->safeAddIndex('matakuliah', 'nama_matakuliah', 'idx_nama_matakuliah');

        // ========== DOSEN TABLE ==========
        $this->safeAddIndex('dosen', 'alamat_email', 'idx_alamat_email');
        $this->safeAddIndex('dosen', 'homebase', 'idx_homebase');

        // ========== TAHUN_AKADEMIK TABLE ==========
        $this->safeAddIndex('tahun_akademik', 'tahun_akademik', 'idx_tahun_akademik');

        // ========== KURIKULUM TABLE ==========
        $this->safeAddIndex('kurikulum', 'kode_nama_kurikulum', 'idx_kode_nama_kurikulum');
        $this->safeAddIndex('kurikulum', 'semester', 'idx_kurikulum_semester');

        // ========== KURIKULUM_ANGKATAN TABLE ==========
        $this->safeAddIndex('kurikulum_angkatan', 'kode_nama_kurikulum', 'idx_kurikulum_angkatan_kode');
        $this->safeAddIndex('kurikulum_angkatan', 'angkatan', 'idx_angkatan');

        // ========== NAMA_KURIKULUM TABLE ==========
        $this->safeAddIndex('nama_kurikulum', 'kode_program_studi', 'idx_nama_kurikulum_prodi');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes safely
        $this->safeDropIndex('mahasiswa', 'idx_program_studi_kode');
        $this->safeDropIndex('mahasiswa', 'idx_email');

        $this->safeDropIndex('krs', 'idx_krs_nim');
        $this->safeDropIndex('krs', 'idx_kode_tahun_akademik');
        $this->safeDropIndex('krs', 'idx_nim_semester');

        $this->safeDropIndex('krs_detail', 'idx_kode_krs');
        $this->safeDropIndex('krs_detail', 'idx_id_matakuliah');

        $this->safeDropIndex('khs_detail', 'idx_kode_krs_detail');

        $this->safeDropIndex('matakuliah', 'idx_matakuliah_prodi');
        $this->safeDropIndex('matakuliah', 'idx_nama_matakuliah');

        $this->safeDropIndex('dosen', 'idx_alamat_email');
        $this->safeDropIndex('dosen', 'idx_homebase');

        $this->safeDropIndex('tahun_akademik', 'idx_tahun_akademik');

        $this->safeDropIndex('kurikulum', 'idx_kode_nama_kurikulum');
        $this->safeDropIndex('kurikulum', 'idx_kurikulum_semester');

        $this->safeDropIndex('kurikulum_angkatan', 'idx_kurikulum_angkatan_kode');
        $this->safeDropIndex('kurikulum_angkatan', 'idx_angkatan');

        $this->safeDropIndex('nama_kurikulum', 'idx_nama_kurikulum_prodi');
    }

    /**
     * Helper to safely drop index
     */
    private function safeDropIndex($table, $indexName)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");

                    return;
                }
            }
        } catch (Exception $e) {
            // Silently continue
        }
    }
};
