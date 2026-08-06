<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_dosen_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_dosen_id');
            $table->string('node_key');
            $table->unsignedBigInteger('kode_dosen');
            $table->timestamps();

            $table->foreign('assessment_dosen_id')->references('id')->on('assessment_dosen')->cascadeOnDelete();
            $table->foreign('kode_dosen')->references('kode_dosen')->on('dosen')->cascadeOnDelete();
            $table->unique(['assessment_dosen_id', 'node_key'], 'uq_assignment_node');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_dosen_assignments');
    }
};
