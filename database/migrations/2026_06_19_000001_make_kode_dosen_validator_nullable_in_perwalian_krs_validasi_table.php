<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perwalian_krs_validasi', function (Blueprint $table) {
            // Drop foreign key first, then make nullable, then re-add
            $table->dropForeign('perwalian_krs_validasi_ibfk_2');

            $table->unsignedBigInteger('kode_dosen_validator')->nullable()->change();

            $table->foreign('kode_dosen_validator', 'perwalian_krs_validasi_ibfk_2')
                ->references('kode_dosen')
                ->on('dosen')
                ->onUpdate('CASCADE');
        });
    }

    public function down(): void
    {
        Schema::table('perwalian_krs_validasi', function (Blueprint $table) {
            $table->dropForeign('perwalian_krs_validasi_ibfk_2');

            $table->unsignedBigInteger('kode_dosen_validator')->nullable(false)->change();

            $table->foreign('kode_dosen_validator', 'perwalian_krs_validasi_ibfk_2')
                ->references('kode_dosen')
                ->on('dosen')
                ->onUpdate('CASCADE');
        });
    }
};
