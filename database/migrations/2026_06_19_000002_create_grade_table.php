<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade', function (Blueprint $table) {
            $table->id();
            $table->float('nilai_min', 5, 2);
            $table->float('nilai_max', 5, 2);
            $table->string('huruf', 2);
            $table->float('skor', 3, 1);
            $table->timestamps();
        });

        // Seed data
        DB::table('grade')->insert([
            ['nilai_min' => 81.00, 'nilai_max' => 100.00, 'huruf' => 'A',  'skor' => 4.0, 'created_at' => now(), 'updated_at' => now()],
            ['nilai_min' => 75.00, 'nilai_max' => 80.99,  'huruf' => 'B+', 'skor' => 3.5, 'created_at' => now(), 'updated_at' => now()],
            ['nilai_min' => 70.00, 'nilai_max' => 74.99,  'huruf' => 'B',  'skor' => 3.0, 'created_at' => now(), 'updated_at' => now()],
            ['nilai_min' => 65.00, 'nilai_max' => 69.99,  'huruf' => 'C+', 'skor' => 2.5, 'created_at' => now(), 'updated_at' => now()],
            ['nilai_min' => 60.00, 'nilai_max' => 64.99,  'huruf' => 'C',  'skor' => 2.0, 'created_at' => now(), 'updated_at' => now()],
            ['nilai_min' => 50.00, 'nilai_max' => 59.99,  'huruf' => 'D',  'skor' => 1.0, 'created_at' => now(), 'updated_at' => now()],
            ['nilai_min' => 0.00,  'nilai_max' => 49.99,  'huruf' => 'E',  'skor' => 0.0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('grade');
    }
};
