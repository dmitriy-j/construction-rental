<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\RentalCondition;
use Illuminate\Database\Seeder;

class RentalConditionsSeeder extends Seeder
{
    public function run()
    {
        // Для каждой компании-арендатора создаем условия аренды
        $lesseeCompanies = Company::where('is_lessee', true)->get();

        foreach ($lesseeCompanies as $company) {
            // Создаем 2 нестандартных условия
            for ($i = 0; $i < 2; $i++) {
                RentalCondition::create([
                    'company_id' => $company->id,
                    'shift_hours' => rand(6, 12),
                    'shifts_per_day' => rand(1, 3),
                    'transportation' => $this->randomTransportation(),
                    'fuel_responsibility' => $this->randomFuelResponsibility(),
                    'extension_policy' => $this->randomExtensionPolicy(),
                    'payment_type' => $this->randomPaymentType(),
                    'delivery_cost_per_km' => rand(50, 200),
                    'loading_cost' => rand(1000, 5000),
                    'unloading_cost' => rand(1000, 5000),
                    'is_default' => false
                ]);
            }

            // Создаем условие по умолчанию
            RentalCondition::create([
                'company_id' => $company->id,
                'shift_hours' => 8,
                'shifts_per_day' => 1,
                'transportation' => 'lessee',
                'fuel_responsibility' => 'lessee',
                'extension_policy' => 'allowed',
                'payment_type' => 'hourly',
                'delivery_cost_per_km' => 100,
                'loading_cost' => 3000,
                'unloading_cost' => 3000,
                'is_default' => true
            ]);
        }
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
