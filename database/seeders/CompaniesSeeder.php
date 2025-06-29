<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\DiscountTier;

class CompaniesSeeder extends Seeder
{
    public function run()
    {
        // Создаем или находим роль company_admin
        $role = Role::firstOrCreate(['name' => 'company_admin']);

        // Удаляем старые данные
        User::where('email', 'admin@stroytech.ru')->delete();
        Company::where('legal_name', 'Тестовая Компания ООО "СтройТех"')->delete();

        // Создаем компанию
        $company = Company::firstOrCreate(
            ['inn' => '7701234569'], // Уникальный идентификатор
            [
                'type' => 'lessor',
                'legal_name' => 'Тестовая Компания ООО "СтройТех"',
                'tax_system' => 'vat',
                'kpp' => '770101001',
                'ogrn' => '1234567890123',
                'okpo' => '12345678',
                'legal_address' => 'г. Москва, ул. Строителей, д. 1',
                'actual_address' => 'г. Москва, ул. Строителей, д. 1',
                'bank_name' => 'ПАО "Сбербанк"',
                'bank_account' => '40702810123450123456',
                'bik' => '044525225',
                'correspondent_account' => '30101810400000000225',
                'director_name' => 'Иванов Иван Иванович',
                'phone' => '+74951234567',
                'contacts' => 'Менеджер: Петрова Мария, +79161234567',
                'contact_email' => 'company@example.com',
                'status' => 'verified',
                'rejection_reason' => null,
                'verified_at' => now(),
            ]
        );

        // Создаем пользователя-администратора для компании
        $user = User::firstOrCreate(
            ['email' => 'admin@stroytech.ru'], // Уникальный идентификатор
            [
                'name' => 'Иванов Иван',
                'phone' => '+74951234567',
                'password' => Hash::make('password'),
                'type' => 'staff',
                'position' => 'admin',
                'company_id' => $company->id,
            ]
        );

        // Назначаем роль (если еще не назначена)
        if (!$user->hasRole('company_admin')) {
            $user->assignRole('company_admin');
        }

        DiscountTier::create([
            'company_id' => $company->id,
            'min_turnover' => 0,
            'discount_percent' => 5
        ]);

        DiscountTier::create([
            'company_id' => $company->id,
            'min_turnover' => 100000,
            'discount_percent' => 10
        ]);
    }
}
