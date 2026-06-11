<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fakultas', function (Blueprint $table) {
            $table->smallIncrements('kode_fakultas');
            $table->string('nama_fakultas', 100);
            $table->unsignedBigInteger('kode_dosen_dekan')->nullable();
            $table->timestamps();

            $table->foreign('kode_dosen_dekan')->references('kode_dosen')->on('dosen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fakultas');
    }
};
