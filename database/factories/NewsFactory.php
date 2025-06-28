<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->unique()->slug,
            'excerpt' => $this->faker->sentence,
            'content' => $this->faker->paragraphs(3, true),
            'publish_date' => now(),
            'is_published' => true,
            'author_id' => Admin::inRandomOrder()->first()->id ?? Admin::factory(),
        ];
    }

    // Состояние для опубликованных новостей
    public function published()
    {
        return $this->state([
            'is_published' => true,
        ]);
    }

    // Состояние для неопубликованных новостей
    public function unpublished()
    {
        return $this->state([
            'is_published' => false,
        ]);
    }
}
