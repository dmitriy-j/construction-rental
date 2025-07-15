<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Category;
use App\Models\Location;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
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
            'rental_terms' => EquipmentRentalTerm::factory()->create(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Equipment $equipment) {
            $isTruck = str_contains($equipment->title, 'Самосвал') ||
                    str_contains($equipment->title, 'Бетононасос');

            $specs = [
                'weight' => $isTruck ? rand(15000, 35000) : rand(5000, 20000),
                'length' => $isTruck ? rand(8, 15) : rand(5, 10),
                'width' => $isTruck ? rand(3, 4) : rand(2, 3),
                'height' => $isTruck ? rand(3, 4) : rand(2, 3)
            ];

            foreach ($specs as $key => $value) {
                Specification::create([
                    'equipment_id' => $equipment->id,
                    'key' => $key,
                    'value' => $value,
                    $key => $value // сохраняем числовое значение
                ]);
            }
        });
    }

}
