<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run()
    {
        // Находим компанию платформы по новому ИНН
        $platformCompany = Company::where('inn', '7716254721')->first();

        if (!$platformCompany) {
            $this->command->error('Platform company not found. Please run CompaniesSeeder first.');
            return;
        }

        try {
            Platform::updateOrCreate(
                ['inn' => '7716254721'],
                [
                    'company_id' => $platformCompany->id,
                    'name' => 'ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ФАП"',
                    'short_name' => 'ООО "ФАП"',
                    'inn' => '7716254721',
                    'kpp' => '771601001',
                    'ogrn' => '1257700474162',
                    'okpo' => '55500712',
                    'okved' => '63.11',
                    'okato' => '45263591000',
                    'certificate_number' => 'Серия 77 №123456789 от 01.01.2024',
                    'ceo_basis' => 'Устав',

                    'legal_address' => '129344, Г.МОСКВА, ВН.ТЕР.Г. МУНИЦИПАЛЬНЫЙ ОКРУГ БАБУШКИНСКИЙ, УЛ ИСКРЫ, Д. 31, К. 1, ПОМЕЩ. 5Ч',
                    'physical_address' => '129344, Г.МОСКВА, ВН.ТЕР.Г. МУНИЦИПАЛЬНЫЙ ОКРУГ БАБУШКИНСКИЙ, УЛ ИСКРЫ, Д. 31, К. 1, ПОМЕЩ. 5Ч',
                    'post_address' => '129344, Г.МОСКВА, ВН.ТЕР.Г. МУНИЦИПАЛЬНЫЙ ОКРУГ БАБУШКИНСКИЙ, УЛ ИСКРЫ, Д. 31, К. 1, ПОМЕЩ. 5Ч',

                    'bank_name' => 'ООО "Банк Точка"',
                    'bank_city' => 'г. Москва',
                    'bic' => '044525104',
                    'correspondent_account' => '30101810745374525104',
                    'settlement_account' => '40702810820000253434',

                    'website' => 'https://fap24.ru',
                    'email' => 'office@fap24.ru',
                    'phone' => '+7 (929) 533-32-06',
                    'additional_phones' => ['+7 (495) 790-90-34'],

                    'ceo_name' => 'Алешин Вячеслав Витальевич',
                    'ceo_position' => 'Генеральный директор',
                    'accountant_name' => 'Алешин Вячеслав Витальевич',
                    'accountant_position' => 'Главный бухгалтер',

                    'notes' => 'Федеральная Арендная Платформа - ваш надежный партнер в аренде строительной техники по всей России',
                ]
            );

            $this->command->info('✅ Платформа ФАП успешно создана и связана с компанией!');
            $this->command->info('📧 Email: office@fap24.ru');
            $this->command->info('📞 Телефон: +7 (929) 533-32-06');
            $this->command->info('🌐 Сайт: https://fap24.ru');

        } catch (\Exception $e) {
            $this->command->error('❌ Ошибка при создании платформы: '.$e->getMessage());

            // Вывести детали для отладки
            $this->command->table(
                ['Поле', 'Значение', 'Длина'],
                [
                    ['correspondent_account', '30101810745374525104', strlen('30101810745374525104')],
                    ['settlement_account', '40702810820000253434', strlen('40702810820000253434')],
                    ['inn', '7716254721', strlen('7716254721')],
                ]
            );
        }
    }
}
