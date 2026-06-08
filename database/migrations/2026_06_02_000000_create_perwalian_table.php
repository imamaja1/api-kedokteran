<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerwalianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('perwalian', function (Blueprint $table) {
            $table->bigIncrements('kode_perwalian');
            $table->char('nim', 11);
            $table->unsignedBigInteger('kode_dosen');
            $table->unsignedBigInteger('kode_dosen_perwakilan')->nullable();
            $table->timestamps();

            $table->index('nim', 'fk_perwalian_mahasiswa');
            $table->index('kode_dosen', 'fk_perwalian_dosen');
            $table->index('kode_dosen_perwakilan', 'fk_perwalian_dosen2');

            $table->foreign('nim', 'perwalian_ibfk_1')
                ->references('nim')
                ->on('mahasiswa')
                ->onUpdate('CASCADE');

            $table->foreign('kode_dosen', 'perwalian_ibfk_2')
                ->references('kode_dosen')
                ->on('dosen')
                ->onUpdate('CASCADE');

            $table->foreign('kode_dosen_perwakilan', 'perwalian_ibfk_3')
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
        Schema::table('perwalian', function (Blueprint $table) {
            $table->dropForeign('perwalian_ibfk_1');
            $table->dropForeign('perwalian_ibfk_2');
            $table->dropForeign('perwalian_ibfk_3');
            $table->dropIndex('fk_perwalian_mahasiswa');
            $table->dropIndex('fk_perwalian_dosen');
            $table->dropIndex('fk_perwalian_dosen2');
        });

        Schema::dropIfExists('perwalian');
    }
}
