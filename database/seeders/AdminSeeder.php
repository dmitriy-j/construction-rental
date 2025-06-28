<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'last_name' => 'Admin',
            'first_name' => 'Super',
            'birth_date' => '1980-01-01',
            'address' => 'Admin Address',
            'phone' => '+1234567890',
            'position' => 'System Administrator',
            'status' => 'active',
        ]);

        // Создаем 5 администраторов через фабрику
        Admin::factory()->count(5)->create();
    }
}
