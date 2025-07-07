<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $admin = User::create([
            'name' => 'Admin Name',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1990-01-01',
            'address' => 'Admin Address',
            'position' => 'Platform Administrator',
            'status' => 'active',
        ]);

        $admin->assignRole('platform_super');
        
        $this->command->info('Администратор платформы создан!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
    }
}