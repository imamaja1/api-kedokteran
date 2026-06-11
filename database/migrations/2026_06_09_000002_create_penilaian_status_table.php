<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kelas_id');
            $table->char('nim', 12);
            $table->uuid('template_id');
            $table->enum('status', ['proses', 'validasi', 'revisi'])->default('proses');
            $table->unsignedBigInteger('dosen_input_by');
            $table->unsignedBigInteger('kaprodi_validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('catatan_dosen')->nullable();
            $table->text('catatan_kaprodi')->nullable();
            $table->timestamps();

            $table->foreign('kelas_id')->references('kelas_id')->on('kelas')->cascadeOnDelete();
            $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('assessment_templates')->cascadeOnDelete();
            $table->foreign('dosen_input_by')->references('kode_dosen')->on('dosen')->cascadeOnDelete();
            $table->foreign('kaprodi_validated_by')->references('kode_dosen')->on('dosen')->nullOnDelete();

            $table->unique(['kelas_id', 'nim'], 'uq_penilaian_kelas_nim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_status');
    }
};
