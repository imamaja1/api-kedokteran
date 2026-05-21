<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========== MAHASISWA TABLE ==========
        Schema::table('mahasiswa', function (Blueprint $table) {
            // Primary search index
            $table->index('nim')->change();
            // Filter by program studi
            $table->index('program_studi_kode');
            // Alternative login
            $table->index('email');
        });

        // ========== KRS TABLE ==========
        Schema::table('krs', function (Blueprint $table) {
            // Join mahasiswa
            $table->index('nim');
            // Filter by tahun akademik
            $table->index('kode_tahun_akademik');
            // Composite index for common queries
            $table->index(['nim', 'semester']);
        });

        // ========== KRS_DETAIL TABLE ==========
        Schema::table('krs_detail', function (Blueprint $table) {
            // Join krs
            $table->index('kode_krs');
            // Join matakuliah
            $table->index('id_matakuliah');
        });

        // ========== KHS_DETAIL TABLE ==========
        Schema::table('khs_detail', function (Blueprint $table) {
            // Join krs_detail
            $table->index('kode_krs_detail');
        });

        // ========== MATAKULIAH TABLE ==========
        Schema::table('matakuliah', function (Blueprint $table) {
            // Filter by program studi
            $table->index('kode_program_studi');
            // Search by name
            $table->index('nama_matakuliah');
        });

        // ========== DOSEN TABLE ==========
        Schema::table('dosen', function (Blueprint $table) {
            // Primary search
            $table->index('kode_dosen')->change();
            // Alternative login
            $table->index('alamat_email');
            // Filter by prodi
            $table->index('homebase');
        });

        // ========== TAHUN_AKADEMIK TABLE ==========
        Schema::table('tahun_akademik', function (Blueprint $table) {
            // Primary search
            $table->index('kode_tahun_akademik')->change();
            // Filter by year
            $table->index('tahun_akademik');
        });

        // ========== KURIKULUM TABLE ==========
        Schema::table('kurikulum', function (Blueprint $table) {
            $table->index('kode_nama_kurikulum');
            $table->index('semester');
        });

        // ========== KURIKULUM_ANGKATAN TABLE ==========
        Schema::table('kurikulum_angkatan', function (Blueprint $table) {
            $table->index('kode_nama_kurikulum');
            $table->index('angkatan');
        });

        // ========== NAMA_KURIKULUM TABLE ==========
        Schema::table('nama_kurikulum', function (Blueprint $table) {
            $table->index('kode_program_studi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropIndex('mahasiswa_program_studi_kode_index');
            $table->dropIndex('mahasiswa_email_index');
        });

        Schema::table('krs', function (Blueprint $table) {
            $table->dropIndex('krs_nim_index');
            $table->dropIndex('krs_kode_tahun_akademik_index');
            $table->dropIndex('krs_nim_semester_index');
        });

        Schema::table('krs_detail', function (Blueprint $table) {
            $table->dropIndex('krs_detail_kode_krs_index');
            $table->dropIndex('krs_detail_id_matakuliah_index');
        });

        Schema::table('khs_detail', function (Blueprint $table) {
            $table->dropIndex('khs_detail_kode_krs_detail_index');
        });

        Schema::table('matakuliah', function (Blueprint $table) {
            $table->dropIndex('matakuliah_kode_program_studi_index');
            $table->dropIndex('matakuliah_nama_matakuliah_index');
        });

        Schema::table('dosen', function (Blueprint $table) {
            $table->dropIndex('dosen_alamat_email_index');
            $table->dropIndex('dosen_homebase_index');
        });

        Schema::table('tahun_akademik', function (Blueprint $table) {
            $table->dropIndex('tahun_akademik_tahun_akademik_index');
        });

        Schema::table('kurikulum', function (Blueprint $table) {
            $table->dropIndex('kurikulum_kode_nama_kurikulum_index');
            $table->dropIndex('kurikulum_semester_index');
        });

        Schema::table('kurikulum_angkatan', function (Blueprint $table) {
            $table->dropIndex('kurikulum_angkatan_kode_nama_kurikulum_index');
            $table->dropIndex('kurikulum_angkatan_angkatan_index');
        });

        Schema::table('nama_kurikulum', function (Blueprint $table) {
            $table->dropIndex('nama_kurikulum_kode_program_studi_index');
        });
    }
};
