<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_dosen', function (Blueprint $table) {
            $table->id();
            $table->uuid('template_id');
            $table->unsignedInteger('kelas_id');
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('assessment_templates')->cascadeOnDelete();
            $table->foreign('kelas_id')->references('kelas_id')->on('kelas')->cascadeOnDelete();
            $table->unique(['template_id', 'kelas_id'], 'uq_assessment_dosen_template_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_dosen');
    }
};
