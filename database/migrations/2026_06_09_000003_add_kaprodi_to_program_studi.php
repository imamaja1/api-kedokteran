<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->unsignedBigInteger('kode_dosen_kaprodi')->nullable()->after('kode_program_studi');
            $table->foreign('kode_dosen_kaprodi')->references('kode_dosen')->on('dosen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->dropForeign(['kode_dosen_kaprodi']);
            $table->dropColumn('kode_dosen_kaprodi');
        });
    }
};
