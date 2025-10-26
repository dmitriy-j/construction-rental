<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Гусеничный экскаватор',
            'Экскаватор-погрузчик',
            'Бульдозер',
            'Фронтальный погрузчик',
            'Кран',
            'Манипулятор',
            'Каток дорожный',
            'Автобетононасос',
            'Автогрейдер',
            'Самосвал',
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'slug' => Str::slug($category),
            ]);
        }
    }
}
