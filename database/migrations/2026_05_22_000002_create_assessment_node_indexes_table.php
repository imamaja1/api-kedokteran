<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_node_indexes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->string('node_key');
            $table->string('parent_key')->nullable();
            $table->string('node_name');
            $table->string('path')->comment('dot.notation path');
            $table->integer('level');
            $table->decimal('weight', 8, 4);
            $table->boolean('is_input')->default(false);
            $table->string('type')->default('category');
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('assessment_templates')->onDelete('cascade');
            $table->index('template_id');
            $table->index('node_key');
            $table->index('is_input');
            $table->unique(['template_id', 'node_key'], 'uq_node_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_node_indexes');
    }
};
