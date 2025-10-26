<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Создаем платформенного администратора
        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@platform.ru',
            'password' => Hash::make('password'),
            'birth_date' => '1985-01-01',
            'address' => 'Platform Headquarters',
            'position' => 'Platform Administrator',
            'status' => 'active',
            'company_id' => null,
        ]);

        $admin->assignRole('platform_super');

        // Создаем несколько пользователей компаний
        User::factory()
            ->count(5)
            ->companyAdmin()
            ->create();

        User::factory()
            ->count(10)
            ->manager()
            ->create();

        User::factory()
            ->count(8)
            ->dispatcher()
            ->create();

        User::factory()
            ->count(7)
            ->accountant()
            ->create();

        // Создаем пользователей без компаний
        User::factory()
            ->count(3)
            ->state(['company_id' => null])
            ->create();
    }
}
