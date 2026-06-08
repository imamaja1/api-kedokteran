<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKelasTable extends Migration
{
    public function up()
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->increments('kelas_id');
            $table->unsignedInteger('nama_kelas_id')->nullable();
            $table->char('semester', 2)->nullable();
            $table->unsignedInteger('kode_tahun_akademik')->nullable();
            $table->char('kode_program_studi', 2)->nullable();
            $table->unsignedInteger('id_matakuliah')->nullable();

            // Index otomatis dibuat oleh foreign key, tidak perlu explicit index
            // $table->index('nama_kelas_id', 'nama_kelas_id');
            // $table->index('id_matakuliah', 'id_matakuliah');

            $table->foreign('nama_kelas_id', 'kelas_ibfk_2')->references('nama_kelas_id')->on('nama_kelas')->onUpdate('CASCADE');
            $table->foreign('id_matakuliah', 'kelas_ibfk_3')->references('id_matakuliah')->on('matakuliah')->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign('kelas_ibfk_2');
            $table->dropForeign('kelas_ibfk_3');
            // $table->dropIndex('nama_kelas_id');
            // $table->dropIndex('id_matakuliah');
        });
        Schema::dropIfExists('kelas');
    }
}
