<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DiscountTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompaniesSeeder extends Seeder
{
    public function run()
    {
        // Удаляем старые данные
        User::where('email', 'admin@stroytech.ru')->delete();
        Company::where('legal_name', 'Тестовая Компания ООО "СтройТех"')->delete();

        // Создаем тестовую компанию (арендодатель)
        $company = Company::create([
            'is_lessor' => true,
            'is_lessee' => false,
            'is_platform' => false, // Добавлено
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

        // Создаем пользователя-администратора для тестовой компании
        $user = User::create([
            'name' => 'Иванов Иван',
            'email' => 'admin@stroytech.ru',
            'phone' => '+74951234567',
            'password' => Hash::make('password'),
            'position' => 'Company Administrator',
            'company_id' => $company->id,
        ]);

        $user->assignRole('company_admin');

        // Создаем компанию для платформы
        $platformCompany = Company::create([
            'is_platform' => true, // Флаг платформы
            'is_lessor' => false,
            'is_lessee' => false,
            'legal_name' => 'ООО "АНЛИМИТ ПАРТС"',
            'tax_system' => 'vat',
            'inn' => '9723125209',
            'kpp' => '772301001',
            'ogrn' => '1217700452133',
            'okpo' => '55500712',
            'legal_address' => '109390, Москва Г., ул. Люблинская, д. 47 пом. IX, ком. 1',
            'actual_address' => '105275, Москва 9-ая ул. Соколиной горы дом 6 с.2',
            'bank_name' => 'ООО "Банк Точка"',
            'bank_account' => '40702810301500108320',
            'bik' => '044525104',
            'correspondent_account' => '30101810745374525104',
            'director_name' => 'Воронцов Евгений Дмитриевич',
            'phone' => '+7 (495) 790-90-34',
            'contacts' => 'Дополнительный телефон: +7 (968) 605-39-49',
            'status' => 'verified',
            'rejection_reason' => null,
            'verified_at' => now(),
        ]);

        // Для компании платформы не создаем пользователя,
        // так как администраторы платформы создаются отдельно

        $companies = Company::all();

        foreach ($companies as $company) {
            DiscountTier::factory()->count(rand(1, 3))->create([
                'company_id' => $company->id,
            ]);
        }
    }
}
