<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DiscountTier;
use Illuminate\Database\Seeder;

class DiscountTierSeeder extends Seeder
{
    public function run()
    {
        // Для всех компаний кроме тестовой
        $companies = Company::where('inn', '!=', '7701234569')->get();

        foreach ($companies as $company) {
            DiscountTier::factory()
                ->count(rand(1, 3))
                ->create(['company_id' => $company->id]);
        }
    }
}