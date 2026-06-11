<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_scores', function (Blueprint $table) {
            $table->unsignedBigInteger('dosen_kode_dosen')->nullable()->after('assessor_id');
            $table->enum('status', ['proses', 'validasi'])->default('proses')->after('dosen_kode_dosen');
            $table->unsignedBigInteger('validated_by')->nullable()->after('status');
            $table->timestamp('validated_at')->nullable()->after('validated_by');

            $table->foreign('dosen_kode_dosen')->references('kode_dosen')->on('dosen')->nullOnDelete();
            $table->foreign('validated_by')->references('kode_dosen')->on('dosen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_scores', function (Blueprint $table) {
            $table->dropForeign(['dosen_kode_dosen']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['dosen_kode_dosen', 'status', 'validated_by', 'validated_at']);
        });
    }
};
