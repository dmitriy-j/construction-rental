<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\RentalCondition;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder; // Добавлен импорт Faker

class RentalConditionsSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create(); // Исправлено создание Faker

        $contracts = Contract::where('is_active', true)->get();

        foreach ($contracts as $contract) {
            $company = $contract->company;
            $locations = $company->locations;

            if ($locations->isEmpty()) {
                continue;
            }

            // Создаем условие по умолчанию
            RentalCondition::create([
                'contract_id' => $contract->id,
                'company_id' => $company->id,
                'shift_hours' => 8,
                'shifts_per_day' => 1,
                'transportation' => 'lessee',
                'fuel_responsibility' => 'lessee',
                'extension_policy' => 'allowed',
                'payment_type' => 'hourly',
                'delivery_location_id' => $locations->first()->id,
                'delivery_cost_per_km' => rand(50, 200),
                'loading_cost' => rand(1000, 5000),
                'unloading_cost' => rand(1000, 5000),
                'is_default' => true,
            ]);

            // Создаем 2 нестандартных условия
            for ($i = 0; $i < 2; $i++) {
                RentalCondition::create([ // Исправлено - добавлены все необходимые поля
                    'contract_id' => $contract->id,
                    'company_id' => $company->id,
                    'shift_hours' => rand(6, 12),
                    'shifts_per_day' => rand(1, 3),
                    'transportation' => $this->randomTransportation(),
                    'fuel_responsibility' => $this->randomFuelResponsibility(),
                    'extension_policy' => $this->randomExtensionPolicy(),
                    'payment_type' => $this->randomPaymentType(),
                    'delivery_location_id' => $locations->random()->id,
                    'delivery_cost_per_km' => rand(50, 200),
                    'loading_cost' => rand(1000, 5000),
                    'unloading_cost' => rand(1000, 5000),
                    'is_default' => false,
                ]);
            } // Добавлена закрывающая скобка цикла for
        } // Исправлено - удален дублирующий код создания условия по умолчанию
    }

    private function randomTransportation(): string
    {
        $options = ['lessor', 'lessee', 'shared'];

        return $options[array_rand($options)];
    }

    private function randomFuelResponsibility(): string
    {
        $options = ['lessor', 'lessee'];

        return $options[array_rand($options)];
    }

    private function randomExtensionPolicy(): string
    {
        $options = ['allowed', 'not_allowed', 'conditional'];

        return $options[array_rand($options)];
    }

    private function randomPaymentType(): string
    {
        $options = ['hourly', 'shift', 'daily', 'mileage', 'volume'];

        return $options[array_rand($options)];
    }
}
