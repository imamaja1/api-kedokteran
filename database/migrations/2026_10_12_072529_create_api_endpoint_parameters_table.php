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
        Schema::create('api_endpoint_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained('api_endpoints')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('type', 50)->default('string');
            $table->string('location', 20)->default('body'); // body, query, path, header
            $table->boolean('is_required')->default(true);
            $table->string('source_type', 20)->default('input'); // input, endpoint, session
            $table->foreignId('source_endpoint_id')->nullable()->constrained('api_endpoints')->onDelete('set null');
            $table->string('source_field', 100)->nullable();
            $table->text('source_note')->nullable();
            $table->text('example')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['api_endpoint_id', 'sort_order']);
            $table->index('source_endpoint_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_endpoint_parameters');
    }
};
