<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\News::factory(5)->create();


          $this->call([
            CategoriesSeeder::class,
            LocationsSeeder::class,
            CompaniesSeeder::class,
            EquipmentSeeder::class,
        ]);

        // Создаем обычного пользователя-арендатора
        /*\App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'tenant', // Исправлено с 'customer' на допустимое значение
            // 'role' удалено - для обычного пользователя должно быть NULL
        ]);

        // Дополнительно: создаем администратора
        \App\Models\User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        */
    }
}
