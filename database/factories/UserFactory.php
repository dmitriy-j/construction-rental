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
            'phone' => $this->faker->phoneNumber,
            'password' => bcrypt('password'),
            'type' => $this->faker->randomElement(['tenant', 'landlord', 'staff']),
            'position' => $this->faker->randomElement(['admin', 'manager', 'dispatcher', 'accountant']),
            'company_id' => Company::factory(),
        ];
    }

    // Состояния
    public function platformAdmin()
    {
        return $this->state([
            'type' => 'staff',
            'position' => 'admin',
            'company_id' => null,
            'position' => null,
        ]);
    }

    public function companyAdmin()
    {
        return $this->state([
            'type' => 'staff',
            'position' => 'admin',
        ])->afterCreating(function (User $user) {
            $user->assignRole('company_admin');
        });
    }

    public function manager()
    {
        return $this->state([
            'type' => 'staff',
            'position' => 'manager',
        ]);
    }

    public function dispatcher()
    {
        return $this->state([
            'type' => 'staff',
            'position' => 'dispatcher',
        ]);
    }

    public function accountant()
    {
        return $this->state([
            'type' => 'staff',
            'position' => 'accountant',
        ]);
    }

    public function verifiedCompany()
    {
        return $this->state([
            'company_id' => Company::factory()->state(['status' => 'verified']),
        ]);
    }

    // В UserFactory.php
    public function withRole($roleName)
    {
        return $this->afterCreating(function (User $user) use ($roleName) {
            $user->assignRole($roleName);
        });
    }
}
