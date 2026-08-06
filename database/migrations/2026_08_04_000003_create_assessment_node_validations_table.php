<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_node_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_dosen_id');
            $table->string('node_key');
            $table->char('nim', 12);
            $table->enum('status', ['belum_input', 'proses', 'validasi', 'revisi'])->default('belum_input');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('assessment_dosen_id')->references('id')->on('assessment_dosen')->cascadeOnDelete();
            $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnDelete();
            $table->foreign('validated_by')->references('kode_dosen')->on('dosen')->nullOnDelete();
            $table->unique(['assessment_dosen_id', 'node_key', 'nim'], 'uq_node_validation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_node_validations');
    }
};
