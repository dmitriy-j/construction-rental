<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Category;
use App\Models\Company;
use App\Models\PlatformMarkup;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory; // Добавляем импорт Faker

class PlatformMarkupSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create(); // Инициализируем Faker

        // Наценки для категорий оборудования
        $categories = Category::all();
        foreach ($categories as $category) {
            PlatformMarkup::updateOrCreate([
                'platform_id' => 1,
                'markupable_type' => Category::class,
                'markupable_id' => $category->id,
            ], [
                'type' => 'percent',
                'value' => $faker->randomFloat(2, 5, 20), // Используем $faker
            ]);
        }

        // Наценки для оборудования
        $equipments = Equipment::inRandomOrder()->take(ceil(Equipment::count() * 0.2))->get();
        foreach ($equipments as $equipment) {
            PlatformMarkup::updateOrCreate([
                'platform_id' => 1,
                'markupable_type' => null,
                'markupable_id' => null,
            ], [
                'type' => 'percent',
                'value' => 10.0,
            ]);
        }

        // Наценки для компаний
        $companies = Company::inRandomOrder()->take(ceil(Company::count() * 0.1))->get();
        foreach ($companies as $company) {
            PlatformMarkup::updateOrCreate([
                'platform_id' => 1,
                'markupable_type' => Company::class,
                'markupable_id' => $company->id,
            ], [
                'type' => 'percent',
                'value' => $faker->randomFloat(2, 3, 15),
            ]);
        }

        // Базовая наценка по умолчанию
        PlatformMarkup::updateOrCreate([
            'platform_id' => 1,
            'markupable_type' => null,
            'markupable_id' => null,
        ], [
            'type' => 'percent',
            'value' => 10.0,
        ]);
    }
}
