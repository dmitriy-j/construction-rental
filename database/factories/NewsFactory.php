<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'content' => $this->faker->paragraphs(3, true),
            'publish_date' => now(),
            'is_published' => true,
            'author_id' => User::factory(),
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
