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
        // Удаляем старые данные
        User::where('email', 'admin@stroytech.ru')->delete();
        Company::where('legal_name', 'Тестовая Компания ООО "СтройТех"')->delete();

        // Создаем компанию
        $company = Company::create([
            'is_lessor' => true,
            'is_lessee' => false,
            'legal_name' => 'Тестовая Компания ООО "СтройТех"',
            'tax_system' => 'vat',
            'inn' => '7701234569',
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
            'status' => 'verified',
            'rejection_reason' => null,
            'verified_at' => now(),
        ]);

        // Создаем пользователя-администратора для компании
        $user = User::create([
            'name' => 'Иванов Иван',
            'email' => 'admin@stroytech.ru',
            'phone' => '+74951234567',
            'password' => Hash::make('password'),
            'position' => 'Company Administrator',
            'company_id' => $company->id,
        ]);

        $user->assignRole('company_admin');

        $companies = Company::all();
    
        foreach ($companies as $company) {
            DiscountTier::factory()->count(rand(1, 3))->create([
                'company_id' => $company->id
            ]);
        }
    }
}