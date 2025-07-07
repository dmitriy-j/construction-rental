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
            'birth_date' => $this->faker->optional()->date(),
            'address' => $this->faker->optional()->address,
            'position' => $this->faker->optional()->jobTitle,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'company_id' => Company::factory(),
        ];
    }

    // Состояния
    public function platformAdmin()
    {
        return $this->state([
            'birth_date' => $this->faker->date(),
            'address' => $this->faker->address,
            'position' => 'Platform Administrator',
            'company_id' => null,
        ])->afterCreating(function (User $user) {
            $user->assignRole('platform_super');
        });
    }

    public function companyAdmin()
    {
        return $this->state([
            'position' => 'Company Administrator',
        ])->afterCreating(function (User $user) {
            $user->assignRole('company_admin');
        });
    }

    public function manager()
    {
        return $this->state([
            'position' => 'Manager',
        ])->afterCreating(function (User $user) {
            $user->assignRole('lessor_manager');
        });
    }

    public function dispatcher()
    {
        return $this->state([
            'position' => 'Dispatcher',
        ])->afterCreating(function (User $user) {
            $user->assignRole('dispatcher');
        });
    }

    public function accountant()
    {
        return $this->state([
            'position' => 'Accountant',
        ])->afterCreating(function (User $user) {
            $user->assignRole('accountant');
        });
    }

    public function withRole($roleName)
    {
        return $this->afterCreating(function (User $user) use ($roleName) {
            $user->assignRole($roleName);
        });
    }
}
