<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perwalian_krs_validasi', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('status_krs');
        });
    }

    public function down(): void
    {
        Schema::table('perwalian_krs_validasi', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }
};
