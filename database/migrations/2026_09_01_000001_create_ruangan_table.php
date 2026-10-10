<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruangan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_ruangan', 50)->unique();
            $table->string('nama_ruangan', 100);
            $table->unsignedSmallInteger('kapasitas')->default(40);
            $table->string('jenis', 50)->default('kuliah'); // kuliah, lab, tutorial, skill_lab, dll
            $table->string('lokasi', 100)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruangan');
    }
};
