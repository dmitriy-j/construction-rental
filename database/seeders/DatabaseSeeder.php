<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Отключить проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Очистка таблиц в правильном порядке (сначала дочерние, затем родительские)
        $tables = [
            'audit_logs',
            'equipment_images',
            'equipment_specifications',
            'equipment_rental_terms',
            'equipment',
            'equipment_categories',
            'locations',
            'news',
            'admins',
            'users',
            'companies',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'password_reset_tokens',
            'password_resets',
            'failed_jobs',
            'personal_access_tokens',
            'jobs',
        ];

        foreach ($tables as $table) {
            DB::table($table)->delete();
            DB::statement("ALTER TABLE $table AUTO_INCREMENT = 1");
        }

        // Включить проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Сначала создаем разрешения и роли
        $this->call(PermissionSeeder::class);

        // 2. Затем создаем компании и пользователей
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            CompaniesSeeder::class,
            DiscountTierSeeder::class,
            UserSeeder::class,
            PlatformSeeder::class,
            PlatformMarkupSeeder::class,
        ]);

        // 3. Затем создаем администраторов
        $this->call(AdminSeeder::class);

        // 4. Затем остальные данные
        $this->call([
            CategoriesSeeder::class,
            LocationsSeeder::class,
            EquipmentSeeder::class,
            PlatformMarkupSeeder::class,
            EquipmentAvailabilitySeeder::class,
            NewsSeeder::class,
        ]);

        // 5. Создаем связанные данные для оборудования
         $this->call([
            EquipmentImageSeeder::class, // Изображения
            EquipmentRentalTermSeeder::class,     // Условия аренды
            OrderSeeder::class,
            
        ]);

        // 5. Новости
       // \App\Models\News::factory(5)->create();
    }
}
