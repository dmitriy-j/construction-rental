<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Отключить проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Правильный порядок очистки (от дочерних к родительским)
        $tables = [
            'audit_logs',
            'equipment_availability',
            'order_items',
            'orders',
            'cart_items',
            'carts',
            'equipment_images',
            'equipment_specifications',
            'equipment_rental_terms',
            'equipment',
            'rental_conditions',
            'contracts',
            'locations',
            'equipment_categories',
            'platform_markups',
            'platforms',
            'discount_tiers',
            'news',
            'admins',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'users',
            'companies',
            'password_reset_tokens',
            'password_resets',
            'failed_jobs',
            'personal_access_tokens',
            'jobs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        // Включить проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Сначала создаем разрешения и роли
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);

        // 2. Затем создаем компании и пользователей
        $this->call(CompaniesSeeder::class);
        $this->call(UserSeeder::class);

        // 3. Затем создаем администраторов
        $this->call(AdminSeeder::class);

        // 4. Платформы и наценки
        $this->call(PlatformSeeder::class);
        $this->call(PlatformMarkupSeeder::class);

        // 5. Категории и локации
        $this->call(CategoriesSeeder::class);
        $this->call(LocationsSeeder::class);

        // 6. Контракты (добавляем перед условиями аренды)
        $this->call(ContractSeeder::class);

        // 7. Оборудование и связанные данные
        $this->call(EquipmentSeeder::class);
        $this->call(EquipmentImageSeeder::class);
        $this->call(EquipmentRentalTermSeeder::class);

        // 8. Условия аренды (после контрактов и локаций)
        $this->call(RentalConditionsSeeder::class);

        // 9. Настройки скидок
        $this->call(DiscountTierSeeder::class);

        // 10. Операторы и другие
        $this->call(OperatorSeeder::class);
        $this->call(EquipmentAvailabilitySeeder::class);

        // 11. Новости
        $this->call(NewsSeeder::class);

        // 12. Заказы
        $this->call(OrderSeeder::class);
    }
}
