<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMengajarTable extends Migration
{
    public function up()
    {
        Schema::create('mengajar', function (Blueprint $table) {
            $table->increments('mengajar_id');
            $table->unsignedBigInteger('kode_dosen')->nullable();
            $table->unsignedInteger('kelas_id')->nullable();

            $table->index('kode_dosen', 'kode_dosen');
            $table->index('kelas_id', 'mengajar_ibfk_2');
        });
    }

    public function down()
    {
        Schema::table('mengajar', function (Blueprint $table) {
            $table->dropIndex('kode_dosen');
            $table->dropIndex('mengajar_ibfk_2');
        });
        Schema::dropIfExists('mengajar');
    }
}
