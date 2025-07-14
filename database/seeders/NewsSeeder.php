<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\News;
use App\Models\User;

class NewsSeeder extends Seeder
{
    public function run()
    {

        $admin = User::where('position', 'platform_admin')->first();

        if (!$admin) {
            $admin = User::factory()->create([
                'position' => 'platform_admin'
            ]);
        }

        News::create([
            'title' => 'Первая новость',
            'slug' => 'pervaya-novost',
            'excerpt' => 'Краткое описание первой новости',
            'content' => 'Полный текст первой новости...',
            'publish_date' => now(),
            'is_published' => true,
            'author_id' => $admin->id, // Убедись, что admin с id=1 существует
        ]);

       
    }
}
