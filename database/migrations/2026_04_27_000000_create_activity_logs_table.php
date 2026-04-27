<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('guard', 30)->nullable();         // mahasiswa_web | dosen_web | staff_web
            $table->string('user_id', 50)->nullable();       // nim / kode_dosen / user.id
            $table->string('user_type', 20)->nullable();     // mahasiswa | dosen | staff
            $table->string('method', 10);                    // GET | POST | PUT | DELETE
            $table->string('path', 255);                     // api/mhs/krs
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
