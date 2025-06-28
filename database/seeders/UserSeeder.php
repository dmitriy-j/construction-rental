<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Создаем платформенного администратора
        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'admin@platform.ru',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'role' => 'platform_super',
            'position' => null,
            'company_id' => null,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'platform_super']);
        $admin->assignRole($adminRole);

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

        // Создаем пользователей без компаний (для тестов)
        User::factory()
            ->count(3)
            ->state(['company_id' => null])
            ->create();
    }
}
