<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->char('nim', 11);
            $table->smallInteger('kode_tahun_akademik')->unsigned();
            $table->enum('status', ['lunas', 'belum'])->default('belum');
            $table->date('tanggal_bayar')->nullable();
            $table->text('keterangan')->nullable();
            $table->smallInteger('sks_override')->unsigned()->nullable();
            $table->text('sks_override_reason')->nullable();
            $table->unsignedBigInteger('sks_override_by')->nullable();
            $table->timestamp('sks_override_at')->nullable();
            $table->timestamps();

            $table->foreign('nim')->references('nim')->on('mahasiswa')->cascadeOnDelete();
            $table->foreign('kode_tahun_akademik')->references('kode_tahun_akademik')->on('tahun_akademik')->cascadeOnDelete();
            $table->foreign('sks_override_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['nim', 'kode_tahun_akademik']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
