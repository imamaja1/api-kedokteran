<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_kelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kelas_id');
            $table->unsignedBigInteger('ruangan_id');
            $table->unsignedBigInteger('hari_id');
            $table->unsignedSmallInteger('kode_tahun_akademik');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->foreign('kelas_id')
                ->references('kelas_id')
                ->on('kelas')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('ruangan_id')
                ->references('id')
                ->on('ruangan')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('hari_id')
                ->references('id')
                ->on('hari')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('kode_tahun_akademik')
                ->references('kode_tahun_akademik')
                ->on('tahun_akademik')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index(['kode_tahun_akademik', 'hari_id', 'ruangan_id'], 'idx_jadwal_lookup');
            $table->index('kelas_id', 'idx_jadwal_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_kelas');
    }
};
