<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mendukung MySQL / MariaDB enum update atau fallback string
        try {
            DB::statement("ALTER TABLE assessment_dosen MODIFY COLUMN status ENUM('aktif', 'non_aktif', 'selesai') NOT NULL DEFAULT 'aktif'");
        } catch (\Throwable $e) {
            // Fallback jika database bukan MySQL murni
            Schema::table('assessment_dosen', function (Blueprint $table) {
                $table->string('status', 20)->default('aktif')->change();
            });
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE assessment_dosen MODIFY COLUMN status ENUM('aktif', 'selesai') NOT NULL DEFAULT 'aktif'");
        } catch (\Throwable $e) {
            // noop
        }
    }
};
