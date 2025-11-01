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
        Company::where('inn', '9723125209')->delete(); // Удаляем старую платформу

        // Создаем тестовую компанию (арендодатель)
        $company = Company::create([
            'is_lessor' => true,
            'is_lessee' => false,
            'is_platform' => false,
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

        // Создаем компанию для платформы с новыми реквизитами ФАП
        $platformCompany = Company::create([
            'is_platform' => true,
            'is_lessor' => false,
            'is_lessee' => false,
            'legal_name' => 'ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ФАП"',
            'tax_system' => 'vat',
            'inn' => '7716254721',
            'kpp' => '771601001',
            'ogrn' => '1257700474162',
            'okpo' => '55500712',
            'legal_address' => '129344, Г.МОСКВА, ВН.ТЕР.Г. МУНИЦИПАЛЬНЫЙ ОКРУГ БАБУШКИНСКИЙ, УЛ ИСКРЫ, Д. 31, К. 1, ПОМЕЩ. 5Ч',
            'actual_address' => '129344, Г.МОСКВА, ВН.ТЕР.Г. МУНИЦИПАЛЬНЫЙ ОКРУГ БАБУШКИНСКИЙ, УЛ ИСКРЫ, Д. 31, К. 1, ПОМЕЩ. 5Ч',
            'bank_name' => 'ООО "Банк Точка"',
            'bank_account' => '40702810820000253434',
            'bik' => '044525104',
            'correspondent_account' => '30101810745374525104',
            'director_name' => 'Алешин Вячеслав Витальевич',
            'phone' => '+7 (929) 533-32-06',
            'contacts' => 'Генеральный директор: Алешин Вячеслав Витальевич',
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

        $this->command->info('Компании успешно созданы!');
        $this->command->info('Платформа: ООО "ФАП" (ИНН: 7716254721)');
        $this->command->info('Тестовая компания: ООО "СтройТех" (ИНН: 7701234569)');
    }
}
