<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sks_rule', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('kode_program_studi');
            $table->float('ip_min', 5, 2);
            $table->float('ip_max', 5, 2);
            $table->unsignedSmallInteger('sks_yang_dapat_diambil');
            $table->timestamps();

            $table->foreign('kode_program_studi')->references('kode_program_studi')->on('program_studi')->restrictOnDelete();
            $table->unique(['kode_program_studi', 'ip_min']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sks_rule');
    }
};
