<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerwalianKrsValidasiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('perwalian_krs_validasi', function (Blueprint $table) {
            $table->bigIncrements('kode_perwalian_krs_validasi');
            $table->char('nim', 11);
            $table->unsignedBigInteger('kode_dosen_validator');
            $table->enum('status_krs', ['A', 'N'])->default('N');
            $table->timestamps();

            $table->index('nim', 'fk_perwalian_krs_validasi_mahasiswa');
            $table->index('kode_dosen_validator', 'fk_perwalian_krs_validasi_dosen_validator');

            $table->foreign('nim', 'perwalian_krs_validasi_ibfk_1')
                ->references('nim')
                ->on('mahasiswa')
                ->onUpdate('CASCADE');

            $table->foreign('kode_dosen_validator', 'perwalian_krs_validasi_ibfk_2')
                ->references('kode_dosen')
                ->on('dosen')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('perwalian_krs_validasi', function (Blueprint $table) {
            $table->dropForeign('perwalian_krs_validasi_ibfk_1');
            $table->dropForeign('perwalian_krs_validasi_ibfk_2');
            $table->dropIndex('fk_perwalian_krs_validasi_mahasiswa');
            $table->dropIndex('fk_perwalian_krs_validasi_dosen_validator');
        });

        Schema::dropIfExists('perwalian_krs_validasi');
    }
}
