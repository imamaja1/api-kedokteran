<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKrsDetailTable extends Migration
{
    public function up()
    {
        Schema::create('krs_detail', function (Blueprint $table) {
            $table->bigIncrements('kode_krs_detail');
            $table->unsignedBigInteger('kode_krs')->nullable();
            $table->enum('status', ['B', 'U', 'K'])->default('B');
            $table->unsignedInteger('id_matakuliah')->nullable();

            $table->timestamps();

            $table->index('kode_krs', 'FK_krs_detail_krs');
            $table->index('id_matakuliah', 'id_matakuliah');

            $table->foreign('kode_krs', 'FK_krs_detail_krs')->references('kode_krs')->on('krs')->onDelete('CASCADE')->onUpdate('CASCADE');
            $table->foreign('id_matakuliah', 'krs_detail_ibfk_1')->references('id_matakuliah')->on('matakuliah')->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('krs_detail', function (Blueprint $table) {
            $table->dropForeign('FK_krs_detail_krs');
            $table->dropForeign('krs_detail_ibfk_1');
            $table->dropIndex('FK_krs_detail_krs');
            $table->dropIndex('id_matakuliah');
        });
        Schema::dropIfExists('krs_detail');
    }
}
