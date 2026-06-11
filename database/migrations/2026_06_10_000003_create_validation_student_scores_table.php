<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create validation_student_scores table
        Schema::create('validation_student_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid('template_id');
            $table->char('nim', 12);
            $table->enum('status', ['proses', 'revisi', 'validasi'])->default('proses');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('assessment_templates')->cascadeOnDelete();
            $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnDelete();
            $table->foreign('validated_by')->references('kode_dosen')->on('dosen')->nullOnDelete();

            $table->unique(['template_id', 'nim'], 'uq_validation_template_nim');
        });

        // Remove tracking columns from student_scores (move to validation_student_scores)
        Schema::table('student_scores', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['status', 'validated_by', 'validated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('student_scores', function (Blueprint $table) {
            $table->enum('status', ['proses', 'revisi', 'validasi'])->default('proses')->after('dosen_kode_dosen');
            $table->unsignedBigInteger('validated_by')->nullable()->after('status');
            $table->timestamp('validated_at')->nullable()->after('validated_by');

            $table->foreign('validated_by')->references('kode_dosen')->on('dosen')->nullOnDelete();
        });

        Schema::dropIfExists('validation_student_scores');
    }
};
