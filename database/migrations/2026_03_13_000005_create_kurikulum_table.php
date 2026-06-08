<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKurikulumTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kurikulum', function (Blueprint $table) {
            $table->bigIncrements('kode_kurikulum');
            $table->unsignedSmallInteger('kode_nama_kurikulum')->nullable();
            $table->char('kode_matakuliah', 10);
            $table->char('semester', 2)->nullable();
            $table->integer('id_matakuliah')->nullable();
            $table->timestamps();

            // Index otomatis dibuat oleh foreign key, tidak perlu explicit index
            // $table->index('kode_nama_kurikulum', 'FK_kurikulum_nama_kurikulum');
            $table->foreign('kode_nama_kurikulum', 'FK_kurikulum_nama_kurikulum')->references('kode_nama_kurikulum')->on('nama_kurikulum')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kurikulum', function (Blueprint $table) {
            $table->dropForeign('FK_kurikulum_nama_kurikulum');
            // $table->dropIndex('FK_kurikulum_nama_kurikulum');
        });
        Schema::dropIfExists('kurikulum');
    }
}
