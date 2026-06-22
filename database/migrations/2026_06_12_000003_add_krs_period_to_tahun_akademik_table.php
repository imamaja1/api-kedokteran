<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_akademik', function (Blueprint $table) {
            $table->date('tanggal_buka_krs')->nullable()->after('tanggal_berakhir');
            $table->date('tanggal_tutup_krs')->nullable()->after('tanggal_buka_krs');
        });
    }

    public function down(): void
    {
        Schema::table('tahun_akademik', function (Blueprint $table) {
            $table->dropColumn(['tanggal_buka_krs', 'tanggal_tutup_krs']);
        });
    }
};
