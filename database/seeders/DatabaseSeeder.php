<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Admin ──
        User::factory()->create([
            'name' => 'Admin YukiMart',
            'email' => 'admin@yukimart.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        // ── Khách lẻ (retail) ──
        User::factory()->create([
            'name' => 'Nguyễn Văn A',
            'email' => 'khachle@yukimart.com',
            'password' => 'password',
            'role' => 'retail',
        ]);

        User::factory()->create([
            'name' => 'Trần Thị B',
            'email' => 'retail@yukimart.com',
            'password' => 'password',
            'role' => 'retail',
        ]);

        // ── Khách sỉ (wholesale) ──
        User::factory()->create([
            'name' => 'Công Ty TNHH ABC',
            'email' => 'khachsi@yukimart.com',
            'password' => 'password',
            'role' => 'wholesale',
        ]);

        User::factory()->create([
            'name' => 'Đại Lý XYZ',
            'email' => 'wholesale@yukimart.com',
            'password' => 'password',
            'role' => 'wholesale',
        ]);
    }
}
