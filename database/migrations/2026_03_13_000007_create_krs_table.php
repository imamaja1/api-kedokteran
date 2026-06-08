<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKrsTable extends Migration
{
    public function up()
    {
        Schema::create('krs', function (Blueprint $table) {
            $table->bigIncrements('kode_krs');
            $table->unsignedSmallInteger('kode_tahun_akademik')->nullable();
            $table->char('nim', 12)->nullable();
            $table->char('semester', 2)->nullable();

            $table->timestamps();

            // Index otomatis dibuat oleh foreign key, tidak perlu explicit index
            // $table->index('kode_tahun_akademik', 'fk_tahun_akademik');
            $table->foreign('kode_tahun_akademik', 'fk_tahun_akademik')->references('kode_tahun_akademik')->on('tahun_akademik')->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('krs', function (Blueprint $table) {
            $table->dropForeign('fk_tahun_akademik');
            // $table->dropIndex('fk_tahun_akademik');
        });
        Schema::dropIfExists('krs');
    }
}
