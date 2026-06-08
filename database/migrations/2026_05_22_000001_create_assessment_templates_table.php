<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop if exists (safe for existing tables)
        Schema::dropIfExists('student_scores');
        Schema::dropIfExists('assessment_node_indexes');
        Schema::dropIfExists('assessment_templates');

        Schema::create('assessment_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('id_matakuliah');
            $table->unsignedSmallInteger('kode_nama_kurikulum');
            $table->unsignedSmallInteger('kode_tahun_akademik');
            $table->integer('versi')->default(1);
            $table->json('structure')->comment('Recursive tree structure');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('id_matakuliah')
                ->references('id_matakuliah')
                ->on('matakuliah')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('kode_nama_kurikulum')
                ->references('kode_nama_kurikulum')
                ->on('nama_kurikulum')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('kode_tahun_akademik')
                ->references('kode_tahun_akademik')
                ->on('tahun_akademik')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // Indexes
            $table->index('id_matakuliah');
            $table->index('kode_nama_kurikulum');
            $table->index('kode_tahun_akademik');
            $table->index('is_active');
            $table->unique(['id_matakuliah', 'kode_nama_kurikulum', 'kode_tahun_akademik', 'versi'], 'uq_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_templates');
    }
};
