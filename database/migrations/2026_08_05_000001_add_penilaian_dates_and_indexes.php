<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add override dates to assessment_dosen (override per kelas)
        Schema::table('assessment_dosen', function (Blueprint $table) {
            $table->date('tanggal_buka')->nullable()->after('status');
            $table->date('tanggal_tutup')->nullable()->after('tanggal_buka');
        });

        // Add global fallback dates to tahun_akademik
        Schema::table('tahun_akademik', function (Blueprint $table) {
            $table->date('tanggal_buka_penilaian')->nullable()->after('tanggal_tutup_krs');
            $table->date('tanggal_tutup_penilaian')->nullable()->after('tanggal_buka_penilaian');
        });

        // Add performance indexes to assessment_node_validations
        Schema::table('assessment_node_validations', function (Blueprint $table) {
            $table->index(['assessment_dosen_id', 'status'], 'idx_ad_id_status');
            $table->index(['assessment_dosen_id', 'node_key', 'status'], 'idx_ad_node_status');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_dosen', function (Blueprint $table) {
            $table->dropColumn(['tanggal_buka', 'tanggal_tutup']);
        });

        Schema::table('tahun_akademik', function (Blueprint $table) {
            $table->dropColumn(['tanggal_buka_penilaian', 'tanggal_tutup_penilaian']);
        });

        Schema::table('assessment_node_validations', function (Blueprint $table) {
            $table->dropIndex('idx_ad_id_status');
            $table->dropIndex('idx_ad_node_status');
        });
    }
};
