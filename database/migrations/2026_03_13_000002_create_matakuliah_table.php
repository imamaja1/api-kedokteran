<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatakuliahTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matakuliah', function (Blueprint $table) {
            $table->increments('id_matakuliah');
            $table->char('kode_matakuliah', 10);
            $table->string('nama_matakuliah', 75);
            $table->smallInteger('jenis')->nullable();
            $table->unsignedTinyInteger('sks_teori')->default(0);
            $table->unsignedTinyInteger('sks_praktik')->default(0);
            $table->smallInteger('kode_kompetensi')->nullable();
            $table->smallInteger('kode_program_studi')->nullable();
            $table->enum('block', ['0', '1']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('matakuliah');
    }
}
