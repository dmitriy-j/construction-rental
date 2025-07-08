<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            'platform_super',
            'platform_admin',
            'platform_support',
            'company_admin',
            'lessor_manager',
            'lessee_manager',
            'dispatcher',
            'accountant'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web'
            ]);
        }
        
        $this->command->info('Роли успешно созданы!');
    }
}