<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKelasMahasiswaTable extends Migration
{
    public function up()
    {
        Schema::create('kelas_mahasiswa', function (Blueprint $table) {
            $table->increments('kelas_mahasiswa_id');
            $table->unsignedBigInteger('kode_krs_detail')->nullable();
            $table->unsignedInteger('kelas_id')->nullable();

            // Index otomatis dibuat oleh foreign key, tidak perlu explicit index
            // $table->index('kode_krs_detail', 'kelas_mahasiswa_ibfk_2');
            // $table->index('kelas_id', 'kelas_mahasiswa_ibfk_1');

            $table->foreign('kelas_id', 'kelas_mahasiswa_ibfk_1')->references('kelas_id')->on('kelas')->onDelete('CASCADE')->onUpdate('CASCADE');
            $table->foreign('kode_krs_detail', 'kelas_mahasiswa_ibfk_2')->references('kode_krs_detail')->on('krs_detail')->onDelete('CASCADE')->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('kelas_mahasiswa', function (Blueprint $table) {
            $table->dropForeign('kelas_mahasiswa_ibfk_1');
            $table->dropForeign('kelas_mahasiswa_ibfk_2');
            // $table->dropIndex('kelas_mahasiswa_ibfk_1');
            // $table->dropIndex('kelas_mahasiswa_ibfk_2');
        });
        Schema::dropIfExists('kelas_mahasiswa');
    }
}
