<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contract;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ContractSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $companies = Company::all();

        foreach ($companies as $company) {
            if ($company->is_lessee) {
                Contract::create([
                    'company_id' => $company->id,
                    'number' => 'ДГ-' . str_pad($company->id, 4, '0', STR_PAD_LEFT),
                    'start_date' => now()->subMonths(6),
                    'end_date' => now()->addYear(),
                    'is_active' => true,
                    'description' => $faker->sentence,
                    'payment_type' => $faker->randomElement(['prepay', 'postpay', 'mixed']),
                    'documentation_deadline' => $faker->numberBetween(1, 30),
                    'payment_deadline' => $faker->numberBetween(1, 30),
                    'penalty_rate' => $faker->randomFloat(2, 0.1, 1),
                    'file_path' => $faker->url,
                    // order_id больше не используется
                ]);
            }

            for ($i = 1; $i <= 2; $i++) {
                Contract::create([
                    'company_id' => $company->id,
                    'number' => 'ДГ-' . str_pad($company->id, 4, '0', STR_PAD_LEFT) . '-' . $i,
                    'start_date' => now()->subMonths(3),
                    'end_date' => now()->addMonths(6),
                    'is_active' => false,
                    'description' => $faker->sentence,
                    'payment_type' => $faker->randomElement(['prepay', 'postpay', 'mixed']),
                    'documentation_deadline' => $faker->numberBetween(1, 30),
                    'payment_deadline' => $faker->numberBetween(1, 30),
                    'penalty_rate' => $faker->randomFloat(2, 0.1, 1),
                    'file_path' => $faker->url,
                    // order_id больше не используется
                ]);
            }
        }
    }
}
