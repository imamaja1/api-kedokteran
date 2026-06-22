<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('khs_detail', function (Blueprint $table) {
            $table->string('grade', 2)->nullable()->after('nilai_akhir');
            $table->float('score', 3, 1)->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('khs_detail', function (Blueprint $table) {
            $table->dropColumn(['grade', 'score']);
        });
    }
};
