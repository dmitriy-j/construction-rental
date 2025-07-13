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
            RentalCondition::factory()->count(2)->create([
                'company_id' => $company->id,
                'is_default' => false
            ]);

            // Создаем условие по умолчанию
            RentalCondition::factory()->create([
                'company_id' => $company->id,
                'is_default' => true
            ]);
        }
    }
}
