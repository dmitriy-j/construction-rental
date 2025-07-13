<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    public function definition(): array
    {
        // Добавляем кастомный провайдер
        $this->faker->addProvider(new \App\Fakers\RussianRegionProvider($this->faker));

        // Используем методы Faker для генерации данных
        $city = $this->faker->city;
        $region = $this->faker->russianRegion(); // Используем метод из провайдера

        return [
            'name' => $this->faker->randomElement([
                'Техническая база',
                'Склад техники',
                'Основной склад',
                'Строительный объект ' . $this->faker->streetName,
                'Площадка ' . $this->faker->buildingNumber
            ]),
            'address' => "{$city}, {$region}, " . $this->faker->streetAddress,
            'latitude' => $this->faker->latitude(55, 60),
            'longitude' => $this->faker->longitude(30, 40),
            'company_id' => Company::inRandomOrder()->first()->id ?? Company::factory(),
        ];
    }
}
