<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade', function (Blueprint $table) {
            $table->unsignedSmallInteger('kode_program_studi')->nullable()->after('id');
            $table->foreign('kode_program_studi')->references('kode_program_studi')->on('program_studi')->restrictOnDelete();
            $table->unique(['kode_program_studi', 'huruf']);
        });
    }

    public function down(): void
    {
        Schema::table('grade', function (Blueprint $table) {
            $table->dropForeign(['kode_program_studi']);
            $table->dropIndex('grade_kode_program_studi_huruf_unique');
            $table->dropColumn('kode_program_studi');
        });
    }
};
