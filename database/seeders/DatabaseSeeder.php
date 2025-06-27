<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {


         // Сначала очищаем таблицы
        \App\Models\User::truncate();
        \App\Models\Company::truncate();
        \App\Models\Admin::truncate();
        \Spatie\Permission\Models\Role::truncate();
        \Spatie\Permission\Models\Permission::truncate();

        // 1. Сначала создаем разрешения и роли
        $this->call(PermissionSeeder::class);

        // 2. Затем создаем компании и пользователей
        $this->call([
            CompaniesSeeder::class,
            UserSeeder::class,
        ]);

        // 3. Затем остальные данные, которые зависят от компаний/пользователей
        $this->call([
            CategoriesSeeder::class,
            LocationsSeeder::class,
            EquipmentSeeder::class,
        ]);

        // 4. Новости (не зависят от других данных)
        \App\Models\News::factory(5)->create();
    }
}
