<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDosenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dosen', function (Blueprint $table) {

            $table->bigIncrements('kode_dosen');
            $table->string('nama_dosen', 255);
            $table->string('field_studi', 255)->nullable();
            $table->string('alumni', 255)->nullable();
            $table->string('nik', 255)->nullable();
            $table->char('no_telp', 20)->nullable();
            $table->enum('status_dosen', ['T', 'L'])->default('T');
            $table->unsignedSmallInteger('homebase')->nullable();
            $table->string('alamat_email', 100)->nullable();
            $table->string('sandi_pengguna', 255)->nullable();
            $table->enum('status_login', ['A', 'N'])->default('N');
            $table->enum('aktif', ['A', 'N'])->default('A');
            $table->string('signature', 200)->nullable();
            $table->string('chatid', 20);

            $table->timestamps();

            // Index otomatis dibuat oleh foreign key, tidak perlu explicit index
            // $table->index('homebase', 'homebase');
            $table->foreign('homebase', 'dosen_ibfk_1')->references('kode_program_studi')->on('program_studi')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dosen', function (Blueprint $table) {
            $table->dropForeign('dosen_ibfk_1');
            // $table->dropIndex('homebase');
        });
        Schema::dropIfExists('dosen');
    }
}
