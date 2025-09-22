<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\Operator;
use Illuminate\Database\Seeder;

class OperatorSeeder extends Seeder
{
    public function run()
    {
        $lessorCompanies = Company::where('is_lessor', true)->get();

        foreach ($lessorCompanies as $company) {
            // Получаем оборудование компании
            $equipmentIds = Equipment::where('company_id', $company->id)
                ->pluck('id')
                ->toArray();

            // Если у компании нет оборудования, пропускаем
            if (empty($equipmentIds)) {
                continue;
            }

            // Создаем операторов для компании
            $operatorCount = rand(5, 10);
            for ($i = 0; $i < $operatorCount; $i++) {
                Operator::create([
                    'company_id' => $company->id,
                    'equipment_id' => $equipmentIds[array_rand($equipmentIds)],
                    'full_name' => fake()->lastName().' '.fake()->firstName(),
                    'phone' => fake()->phoneNumber(),
                    'license_number' => 'LN-'.rand(1000, 9999),
                    'qualification' => fake()->randomElement(['Экскаваторщик', 'Крановщик', 'Бульдозерист', 'Погрузчик']),
                    'is_active' => rand(0, 1) ? true : false,
                ]);
            }
        }

        $this->command->info('Operators created: '.Operator::count());
    }
}
