<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
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

        // Создаем демо-оборудование
        $this->createEquipmentWithTerms([
            'title' => 'Экскаватор JCB',
            'description' => 'Мощный экскаватор для земляных работ',
            'company_id' => $this->getRandomId($this->companyIds),
            'category_id' => $this->getRandomId($this->categoryIds),
            'location_id' => $this->getRandomId($this->locationIds),
            'brand' => 'JCB',
            'model' => '3CX',
            'year' => 2020,
            'hours_worked' => 500,
            'is_approved' => true,
        ], $progressBar);

        // Создаем 49 единиц оборудования
        for ($i = 0; $i < 49; $i++) {
            $this->createRandomEquipment($progressBar);
        }

        $progressBar->finish();
        $this->command->info("\nEquipment seeding completed successfully.");
    }

    protected function getRandomId(array $ids)
    {
        return $ids[array_rand($ids)];
    }

    protected function createEquipmentWithTerms(array $data, $progressBar = null)
    {
        $slug = Str::slug($data['title']) . '-' . Str::random(6);

        // Гарантируем уникальность slug
        while (in_array($slug, $this->usedSlugs)) {
            $slug = Str::slug($data['title']) . '-' . Str::random(6);
        }

        $this->usedSlugs[] = $slug;
        $data['slug'] = $slug;

        $equipment = Equipment::create($data);

        // Гарантированное создание условий аренды и характеристик
        $this->createRentalTerms($equipment);
        $this->createSpecifications($equipment);

        if ($progressBar) {
            $progressBar->advance();
        }

        return $equipment;
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

        return $this->createEquipmentWithTerms([
            'title' => $title,
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
        ], $progressBar);
    }

    protected function createRentalTerms(Equipment $equipment)
    {
        $periods = ['час', 'смена', 'сутки', 'месяц'];
        shuffle($periods);

        // Гарантированно создаем минимум 1 условие
        $count = max(1, rand(1, 4));
        $terms = [];
        $now = now();

        foreach (array_slice($periods, 0, $count) as $period) {
            $terms[] = [
                'equipment_id' => $equipment->id,
                'period' => $period,
                'price' => $this->getPriceForPeriod($period),
                'currency' => 'RUB',
                'delivery_price' => rand(500, 5000),
                'delivery_days' => rand(1, 3),
                'return_policy' => $this->getReturnPolicyForPeriod($period),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // Массовая вставка условий аренды
        EquipmentRentalTerm::insert($terms);
    }

    protected function getPriceForPeriod($period)
    {
        return match($period) {
            'час' => rand(500, 2000),
            'смена' => rand(5000, 15000),
            'сутки' => rand(10000, 30000),
            'месяц' => rand(100000, 500000),
            default => rand(1000, 5000),
        };
    }

    protected function getReturnPolicyForPeriod($period)
    {
        return match($period) {
            'час' => 'Минимальное бронирование: 4 часа',
            'смена' => 'Возврат при отмене за 24 часа',
            'сутки' => 'Возврат при отмене за 48 часов',
            'месяц' => 'Возврат при отмене за 7 дней',
            default => 'Стандартные условия возврата',
        };
    }

    protected function createSpecifications(Equipment $equipment)
    {
        $specs = [
            'Вес' => rand(1000, 20000) . ' кг',
            'Мощность' => rand(50, 500) . ' л.с.',
            'Габариты' => rand(2, 10) . 'x' . rand(2, 5) . 'x' . rand(2, 4) . ' м',
            'Глубина копания' => rand(3, 10) . ' м',
            'Грузоподъемность' => rand(1, 20) . ' т',
            'Расход топлива' => rand(10, 50) . ' л/час',
            'Тип двигателя' => fake()->randomElement(['Дизель', 'Бензин', 'Электрический']),
        ];

        $specifications = [];
        $now = now();

        // Выбираем 3-7 случайных характеристик
        $keys = array_keys($specs);
        shuffle($keys);
        $selectedKeys = array_slice($keys, 0, rand(3, min(7, count($keys))));

        foreach ($selectedKeys as $key) {
            $specifications[] = [
                'equipment_id' => $equipment->id,
                'key' => $key,
                'value' => $specs[$key],
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // Массовая вставка характеристик
        Specification::insert($specifications);
    }
}
