<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('student_scores');

        Schema::create('student_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->string('nim');
            $table->string('node_key');
            $table->decimal('score', 5, 2)->nullable();
            $table->unsignedBigInteger('assessor_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('template_id')->references('id')->on('assessment_templates')->onDelete('cascade');
            $table->foreign('nim')->references('nim')->on('mahasiswa')->onDelete('cascade');
            $table->foreign('assessor_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index('template_id');
            $table->index('nim');
            $table->index('node_key');
            $table->unique(['template_id', 'nim', 'node_key'], 'uq_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_scores');
    }
};
