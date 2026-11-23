<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_group_mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kelas_group_id');
            $table->char('nim', 11);
            $table->timestamps();

            $table->foreign('kelas_group_id')->references('id')->on('kelas_groups')->cascadeOnDelete();
            $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnDelete();

            $table->unique(['kelas_group_id', 'nim'], 'uq_group_nim');
            $table->index('kelas_group_id');
            $table->index('nim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_group_mahasiswas');
    }
};
