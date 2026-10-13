<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_workflows', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('title', 255);
            $table->string('badge', 100)->nullable();
            $table->string('icon', 50)->default('route');
            $table->text('summary');
            $table->json('roles');
            $table->json('rules');
            $table->json('phases');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_workflows');
    }
};
