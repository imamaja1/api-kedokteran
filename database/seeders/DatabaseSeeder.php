<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Default admin
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@siakad.ac.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Default staff
        User::factory()->create([
            'name' => 'Staff Akademik',
            'email' => 'staff@siakad.ac.id',
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);

        // Seed dokumentasi API dari route yang sudah ada
        $this->call(ApiDocSeeder::class);
    }
}
