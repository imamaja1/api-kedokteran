<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_connections', function (Blueprint $table) {
            $table->text('cookie')->nullable()->after('password');          // Cookie session hasil login
            $table->json('extra_headers')->nullable()->after('cookie');     // Header tambahan (opsional)
        });
    }

    public function down(): void
    {
        Schema::table('api_connections', function (Blueprint $table) {
            $table->dropColumn(['cookie', 'extra_headers']);
        });
    }
};
