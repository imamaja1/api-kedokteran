<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->unsignedSmallInteger('kode_fakultas')->nullable()->after('singkatan_program_studi');
            $table->foreign('kode_fakultas')->references('kode_fakultas')->on('fakultas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('program_studi', function (Blueprint $table) {
            $table->dropForeign(['kode_fakultas']);
            $table->dropColumn('kode_fakultas');
        });
    }
};
