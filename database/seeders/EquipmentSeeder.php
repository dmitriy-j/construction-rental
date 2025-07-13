<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Company;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;
use App\Models\Specification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EquipmentSeeder extends Seeder
{
    private $usedSlugs = [];
    private $companyIds;
    private $categoryIds;
    private $locationIds;

    public function run()
    {
        $this->command->info('Starting equipment seeding...');
        $progressBar = $this->command->getOutput()->createProgressBar(50);
        $progressBar->start();

        // Получаем существующие ID один раз
        $this->companyIds = Company::pluck('id')->toArray();
        $this->categoryIds = Category::pluck('id')->toArray();
        $this->locationIds = Location::pluck('id')->toArray();

        if (empty($this->companyIds)) {
            $this->call(CompaniesSeeder::class);
            $this->companyIds = Company::pluck('id')->toArray();
        }

        if (empty($this->categoryIds)) {
            $this->call(CategoriesSeeder::class);
            $this->categoryIds = Category::pluck('id')->toArray();
        }

        if (empty($this->locationIds)) {
            $this->call(LocationsSeeder::class);
            $this->locationIds = Location::pluck('id')->toArray();
        }

        // Создаем 50 единиц оборудования
        for ($i = 0; $i < 50; $i++) {
            $this->createRandomEquipment($progressBar);
        }

        $progressBar->finish();
        $this->command->info("\nEquipment seeding completed successfully.");
    }

    protected function getRandomId(array $ids)
    {
        return $ids[array_rand($ids)];
    }

    protected function createRandomEquipment($progressBar = null)
    {
        $types = [
            'Гусеничный экскаватор', 'Экскаватор-погрузчик', 'Бульдозер',
            'Фронтальный погрузчик', 'Кран', 'Манипулятор', 'Каток дорожный',
            'Автобетононасос', 'Автогрейдер', 'Самосвал'
        ];

        $brands = ['JCB', 'Caterpillar', 'Komatsu', 'Hitachi', 'Volvo', 'Liebherr', 'Doosan', 'Hyundai'];
        $models = ['X-500', 'HD-300', 'ZX-210', 'PC-200', 'EC-480', 'R-974', 'D-65', 'L-350'];

        $title = fake()->randomElement($types) . ' ' . fake()->randomElement($brands) . ' ' . fake()->randomElement($models);
        $slug = Str::slug($title) . '-' . Str::random(6);

        // Гарантируем уникальность slug
        while (in_array($slug, $this->usedSlugs)) {
            $slug = Str::slug($title) . '-' . Str::random(6);
        }

        $this->usedSlugs[] = $slug;

        $equipment = Equipment::create([
            'title' => $title,
            'slug' => $slug,
            'description' => fake()->paragraph(3),
            'company_id' => $this->getRandomId($this->companyIds),
            'category_id' => $this->getRandomId($this->categoryIds),
            'location_id' => $this->getRandomId($this->locationIds),
            'brand' => fake()->randomElement($brands),
            'model' => fake()->randomElement($models),
            'year' => fake()->numberBetween(2015, 2024),
            'hours_worked' => fake()->numberBetween(100, 5000),
            'is_approved' => fake()->boolean(90),
            'rating' => fake()->randomFloat(1, 3, 5),
            'is_featured' => fake()->boolean(30),
        ]);

        // Создаем характеристики
        $this->createSpecifications($equipment);

        if ($progressBar) {
            $progressBar->advance();
        }

        return $equipment;
    }

    protected function createSpecifications(Equipment $equipment)
    {
        $isTruck = str_contains($equipment->title, 'Самосвал') ||
                str_contains($equipment->title, 'Бетононасос');

        // Системные характеристики (сохраняем числовые значения)
        $systemSpecs = [
            'weight' => $isTruck ? rand(15000, 35000) : rand(5000, 20000),
            'length' => $isTruck ? rand(8, 15) : rand(5, 10),
            'width' => $isTruck ? rand(3, 4) : rand(2, 3),
            'height' => $isTruck ? rand(3, 4) : rand(2, 3),
        ];

        // Пользовательские характеристики (для отображения)
        $displaySpecs = [
            'Вес' => $systemSpecs['weight'] . ' кг',
            'Длина' => $systemSpecs['length'] . ' м',
            'Ширина' => $systemSpecs['width'] . ' м',
            'Высота' => $systemSpecs['height'] . ' м',
            'Мощность' => rand(50, 500) . ' л.с.',
            'Грузоподъемность' => rand(1, 20) . ' т',
            'Расход топлива' => rand(10, 50) . ' л/час',
            'Тип двигателя' => fake()->randomElement(['Дизель', 'Бензин', 'Электрический']),
        ];

        $specifications = [];
        $now = now();

        foreach ($displaySpecs as $key => $value) {
            $spec = [
                'equipment_id' => $equipment->id,
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
                'weight' => null,
                'length' => null,
                'width' => null,
                'height' => null
            ];

            // Сохраняем числовые значения для ключевых характеристик
            if ($key === 'Вес') $spec['weight'] = $systemSpecs['weight'];
            if ($key === 'Длина') $spec['length'] = $systemSpecs['length'];
            if ($key === 'Ширина') $spec['width'] = $systemSpecs['width'];
            if ($key === 'Высота') $spec['height'] = $systemSpecs['height'];

            $specifications[] = $spec;
        }

        Specification::insert($specifications);
    }

}
