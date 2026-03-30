<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNamaKurikulumTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nama_kurikulum', function (Blueprint $table) {
            $table->smallIncrements('kode_nama_kurikulum');
            $table->char('nama_kurikulum', 20);
            $table->unsignedSmallInteger('kode_program_studi');
            $table->string('angkatan1', 255)->nullable();
            $table->enum('ekstensi1', ['Y', 'N'])->nullable();
            $table->enum('paket1', ['Y', 'N'])->nullable();

            $table->timestamps();
            $table->index('kode_program_studi', 'FK_nama_kurikulum_program_studi');
            $table->foreign('kode_program_studi', 'FK_nama_kurikulum_program_studi')->references('kode_program_studi')->on('program_studi')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nama_kurikulum', function (Blueprint $table) {
            $table->dropForeign('FK_nama_kurikulum_program_studi');
            $table->dropIndex('FK_nama_kurikulum_program_studi');
        });
        Schema::dropIfExists('nama_kurikulum');
    }
}
