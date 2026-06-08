<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKhsDetailTable extends Migration
{
    public function up()
    {
        Schema::create('khs_detail', function (Blueprint $table) {
            $table->bigIncrements('kode_khs_detail');
            $table->unsignedBigInteger('kode_krs_detail')->nullable();
            $table->float('nilai_akhir', 10, 2)->nullable();
            $table->enum('tidak_berhak', ['A','N'])->nullable();
            $table->timestamps();
            // Index otomatis dibuat oleh foreign key, tidak perlu explicit index
            // $table->index('kode_krs_detail', 'FK_khs_detail_krs_detail');
            $table->foreign('kode_krs_detail', 'khs_detail_ibfk_1')->references('kode_krs_detail')->on('krs_detail')->onDelete('CASCADE')->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::table('khs_detail', function (Blueprint $table) {
            $table->dropForeign('khs_detail_ibfk_1');
            // $table->dropIndex('FK_khs_detail_krs_detail');
        });
        Schema::dropIfExists('khs_detail');
    }
}
