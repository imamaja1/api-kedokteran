<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Nama koneksi, misal: "SISKA API", "Feeder Dikti"
            $table->text('description')->nullable();         // Keterangan opsional
            $table->string('base_url');                      // Base URL API, misal: https://api.kampus.ac.id
            $table->string('username')->nullable();          // Username untuk login
            $table->text('password')->nullable();            // Password untuk login (disimpan terenkripsi)
            $table->timestamp('cookie_expires_at')->nullable(); // Waktu kadaluarsa cookie
            $table->boolean('is_active')->default(true);     // Status aktif/nonaktif koneksi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_connections');
    }
};
