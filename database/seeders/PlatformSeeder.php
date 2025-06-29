<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run()
    {
          try {
            Platform::updateOrCreate(['inn' => '9723125209'], [
                'name' => 'Общество с ограниченной ответственностью «АНЛИМИТ ПАРТС»',
                'short_name' => 'ООО «АНЛИМИТ ПАРТС»',
                'inn' => '9723125209',
                'kpp' => '772301001',
                'ogrn' => '1217700452133',
                'okpo' => '55500712',
                'okved' => '63.11',
                'okato' => '45263591000',
                'certificate_number' => 'Серия 77 №123456789 от 01.01.2025',
                'ceo_basis' => 'Устав',

                'legal_address' => '109390, Москва Г., ул. Люблинская, д. 47 пом. IX, ком. 1',
                'physical_address' => '105275, Москва 9-ая ул. Соколиной горы дом 6 с.2',
                'post_address' => '105275, Москва 9-ая ул. Соколиной горы дом 6 с.2',

                'bank_name' => 'ООО "Банк Точка"',
                'bank_city' => 'г. Москва',
                'bic' => '044525104',
                'correspondent_account' => '30101810745374525104',
                'settlement_account' => '40702810301500108320',

                'website' => 'https://unparts.ru',
                'email' => 'office@unparts.ru',
                'phone' => '+7 (495) 790-90-34',
                'additional_phones' => ['+7 (968) 605-39-49'],

                'ceo_name' => 'Воронцов Евгений Дмитриевич',
                'ceo_position' => 'Генеральный директор',
                'accountant_name' => 'Воронцов Евгений Дмитриевич',
                'accountant_position' => 'Главный бухгалтер',

                'notes' => 'Для отправки корреспонденции использовать фактический адрес',
            ]);
        } catch (\Exception $e) {
        $this->command->error("Ошибка: " . $e->getMessage());
         // Вывести длину критичных значений
        $accounts = [
            'correspondent' => '30101810745374525104',
            'settlement' => '40702810301500108320'
        ];

        $this->command->table(
            ['Поле', 'Длина'],
            [
                ['correspondent_account', strlen($accounts['correspondent'])],
                ['settlement_account', strlen($accounts['settlement'])]
            ]
        );

            $this->command->info('Платформа успешно создана!');
        }
    }
}
