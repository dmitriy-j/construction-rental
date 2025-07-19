<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CarrierCompaniesSeeder extends Seeder
{
    public function run()
    {
        Company::create([
            'legal_name' => 'ООО "ТрансЛогистик"',
            'ogrn' => '1234567890123', // 13 цифр
            'tax_system' => 'vat', // обязательное enum-поле
            'inn' => '7701234567',
            'kpp' => '770101001',
            'is_carrier' => true,
            'legal_address' => 'г. Москва, ул. Транспортная, д. 25',
            'actual_address' => 'г. Москва, ул. Транспортная, д. 25',
            'bank_name' => 'ПАО Сбербанк',
            'bank_account' => '40702810500000012345',
            'bik' => '044525225',
            'correspondent_account' => '30101810400000000225',
            'director_name' => 'Иванов Иван Иванович',
            'phone' => '+74951234567',
            'contacts' => 'Сидоров Алексей, менеджер по перевозкам: +79991112233',
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        Company::create([
            'legal_name' => 'ООО "ГрузовичкоФ"',
            'ogrn' => '1234567890123', // 13 цифр
            'tax_system' => 'vat', // обязательное enum-поле
            'inn' => '7809876543',
            'kpp' => '780101001',
            'is_carrier' => true,
            'legal_address' => 'г. Санкт-Петербург, пр. Грузовой, д. 8',
            'actual_address' => 'г. Санкт-Петербург, пр. Грузовой, д. 8',
            'bank_name' => 'ПАО ВТБ',
            'bank_account' => '40702810600000067890',
            'bik' => '044525187',
            'correspondent_account' => '30101810700000000187',
            'director_name' => 'Петров Петр Петрович',
            'phone' => '+78122456789',
            'contacts' => 'Васильева Мария, логист: +79994445566',
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }
}
