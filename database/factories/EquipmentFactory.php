<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        $types = [
            'Гусеничный экскаватор', 'Экскаватор-погрузчик', 'Бульдозер',
            'Фронтальный погрузчик', 'Кран', 'Манипулятор', 'Каток дорожный',
            'Автобетононасос', 'Автогрейдер', 'Самосвал'
        ];

        $brands = ['JCB', 'Caterpillar', 'Komatsu', 'Hitachi', 'Volvo', 'Liebherr', 'Doosan', 'Hyundai'];
        $models = ['X-500', 'HD-300', 'ZX-210', 'PC-200', 'EC-480', 'R-974', 'D-65', 'L-350'];

        $title = $this->faker->randomElement($types) . ' ' . $this->faker->randomElement($brands) . ' ' . $this->faker->randomElement($models);

        return [
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
            'description' => $this->faker->paragraph(3),
            'company_id' => Company::inRandomOrder()->first()->id,
            'category_id' => Category::inRandomOrder()->first()->id,
            'location_id' => Location::inRandomOrder()->first()->id,
            'brand' => $this->faker->randomElement($brands),
            'model' => $this->faker->randomElement($models),
            'year' => $this->faker->numberBetween(2015, 2024),
            'hours_worked' => $this->faker->numberBetween(100, 5000),
            'is_approved' => $this->faker->boolean(90), // 90% chance true
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'is_featured' => $this->faker->boolean(30),
        ];
    }
}
