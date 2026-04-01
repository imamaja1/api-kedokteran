<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNamaKelasTable extends Migration
{
    public function up()
    {
        Schema::create('nama_kelas', function (Blueprint $table) {
            $table->increments('nama_kelas_id');
            $table->char('nama_kelas', 2)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nama_kelas');
    }
}
