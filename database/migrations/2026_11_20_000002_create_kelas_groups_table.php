<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kelas_id');
            $table->string('node_key', 100);
            $table->string('nama_group', 50);
            $table->unsignedBigInteger('kode_dosen')->nullable();
            $table->timestamps();

            $table->foreign('kelas_id')->references('kelas_id')->on('kelas')->cascadeOnDelete();
            $table->foreign('kode_dosen')->references('kode_dosen')->on('dosen')->nullOnDelete();

            $table->index(['kelas_id', 'node_key']);
            $table->unique(['kelas_id', 'node_key', 'nama_group'], 'uq_kelas_node_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_groups');
    }
};
