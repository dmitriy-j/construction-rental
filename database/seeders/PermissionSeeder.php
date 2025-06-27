<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {

        \Spatie\Permission\Models\Permission::truncate();
        \Spatie\Permission\Models\Role::truncate();

        // Создаем разрешения
        $permissions = [
            'manage users',
            'manage equipment',
            'manage orders',
            'manage finances',
            'view reports',
            'manage company settings',
            'moderate content',
            'manage platform settings'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Создаем роли и назначаем разрешения
        $this->createPlatformRoles($permissions);
        $this->createCompanyRoles($permissions);
    }

    private function createPlatformRoles($permissions)
    {
        // Суперадмин - все права
        $superAdmin = Role::firstOrCreate(['name' => 'platform_super']);
        $superAdmin->syncPermissions($permissions);

        // Менеджер платформы
        $manager = Role::firstOrCreate(['name' => 'platform_manager']);
        $manager->syncPermissions([
            'manage users',
            'moderate content',
            'view reports'
        ]);

        // Модератор платформы
        $moderator = Role::firstOrCreate(['name' => 'platform_moder']);
        $moderator->syncPermissions([
            'moderate content',
            'view reports'
        ]);

        // Техподдержка
        $support = Role::firstOrCreate(['name' => 'platform_support']);
        $support->syncPermissions([
            'view reports'
        ]);
    }

    private function createCompanyRoles($permissions)
    {
        // Администратор компании - большинство прав
        $companyAdmin = Role::firstOrCreate(['name' => 'company_admin']);
        $companyAdmin->syncPermissions([
            'manage users',
            'manage equipment',
            'manage orders',
            'manage finances',
            'view reports',
            'manage company settings'
        ]);

        // Менеджер компании
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'manage equipment',
            'manage orders',
            'view reports'
        ]);

        // Диспетчер
        $dispatcher = Role::firstOrCreate(['name' => 'dispatcher']);
        $dispatcher->syncPermissions([
            'manage orders',
            'view reports'
        ]);

        // Бухгалтер
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->syncPermissions([
            'manage finances',
            'view reports'
        ]);
    }
}
