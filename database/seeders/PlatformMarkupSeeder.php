<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\PlatformMarkup;
use Illuminate\Database\Seeder;

class PlatformMarkupSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();

        // ========== НАЦЕНКИ ДЛЯ ПРЯМЫХ ЗАКАЗОВ (каталог) ==========

        // Наценки для категорий оборудования (заказы)
        $categories = Category::all();
        foreach ($categories as $category) {
            PlatformMarkup::updateOrCreate([
                'platform_id' => 1,
                'markupable_type' => Category::class,
                'markupable_id' => $category->id,
                'entity_type' => 'order' // Явно указываем для заказов
            ], [
                'type' => 'percent',
                'value' => $faker->randomFloat(2, 5, 20),
            ]);
        }

        // Наценки для оборудования (заказы)
        $equipments = Equipment::inRandomOrder()->take(ceil(Equipment::count() * 0.2))->get();
        foreach ($equipments as $equipment) {
            PlatformMarkup::updateOrCreate([
                'platform_id' => 1,
                'markupable_type' => Equipment::class,
                'markupable_id' => $equipment->id,
                'entity_type' => 'order'
            ], [
                'type' => 'percent',
                'value' => $faker->randomFloat(2, 8, 25),
            ]);
        }

        // Наценки для компаний (заказы)
        $companies = Company::inRandomOrder()->take(ceil(Company::count() * 0.1))->get();
        foreach ($companies as $company) {
            PlatformMarkup::updateOrCreate([
                'platform_id' => 1,
                'markupable_type' => Company::class,
                'markupable_id' => $company->id,
                'entity_type' => 'order'
            ], [
                'type' => 'percent',
                'value' => $faker->randomFloat(2, 3, 15),
            ]);
        }

        // Базовая наценка по умолчанию для заказов
        PlatformMarkup::updateOrCreate([
            'platform_id' => 1,
            'markupable_type' => null,
            'markupable_id' => null,
            'entity_type' => 'order'
        ], [
            'type' => 'percent',
            'value' => 10.0,
        ]);

        // ========== НАЦЕНКИ ДЛЯ ЗАЯВОК (rental_requests) ==========

        // Фиксированная наценка 100₽ для всех заявок
        PlatformMarkup::updateOrCreate([
            'platform_id' => 1,
            'markupable_type' => null,
            'markupable_id' => null,
            'entity_type' => 'rental_request'
        ], [
            'type' => 'fixed',
            'value' => 100.00,
        ]);

        $this->command->info('✅ Наценки для заказов и заявок созданы успешно!');
        $this->command->info('📊 Заказы: процентные наценки по категориям, оборудованию и компаниям');
        $this->command->info('📝 Заявки: фиксированная наценка 100₽ для всех заявок');
    }
}
