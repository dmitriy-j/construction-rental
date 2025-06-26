<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompaniesSeeder extends Seeder
{
    public function run()
    {
        Company::create([
            'type' => 'lessor',
            'legal_name' => 'Тестовая Компания ООО "СтройТех"',
            'tax_system' => 'vat',
            'inn' => '7701234569',
            'kpp' => '770101001',
            'ogrn' => '1234567890123',
            'okpo' => '12345678',
            'legal_address' => 'г. Москва, ул. Строителей, д. 1',
            'actual_address' => 'г. Москва, ул. Строителей, д. 1',
            'bank_name' => 'ПАО "Сбербанк"',
            'bank_account' => '40702810123450123456', // 20 символов
            'bik' => '044525225',
            'correspondent_account' => '30101810400000000225',
            'director_name' => 'Иванов Иван Иванович',
            'phone' => '+74951234567',
            'contacts' => 'Менеджер: Петрова Мария, +79161234567',
            'email' => 'company@example.com',
            'password' => Hash::make('password'),
            'status' => 'verified',
            'rejection_reason' => null,
            'verified_at' => now(),
        ]);
    }
}
