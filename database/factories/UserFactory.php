<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Company;

class UserFactory extends Factory
{
    protected $model = User::class;

        public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'type' => 'tenant',
            'role' => null,
            'company_id' => Company::factory(),
        ];
    }

    // Состояния
    public function platformAdmin()
    {
        return $this->state([
            'type' => 'admin',
            'role' => 'platform_super',
            'company_id' => null,
        ]);
    }

    public function verifiedCompany()
    {
        return $this->state([
            'company_id' => Company::factory()->state(['status' => 'verified']),
        ]);
    }
}
