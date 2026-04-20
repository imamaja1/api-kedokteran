<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKurikulumAngkatanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kurikulum_angkatan', function (Blueprint $table) {
            $table->increments('kode_kurikulum_angkatan');
            $table->string('angkatan')->nullable();
            $table->enum('ekstensi', ['Y', 'N'])->default('N')->nullable();
            $table->enum('paket', ['Y', 'N'])->default('N')->nullable();
            $table->smallInteger('kode_nama_kurikulum')->nullable();

            $table->index('kode_nama_kurikulum');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kurikulum_angkatan');
    }
}
