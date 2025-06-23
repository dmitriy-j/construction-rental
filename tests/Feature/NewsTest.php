<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_news_list()
    {
        News::factory(5)->published()->create();

        $response = $this->getJson('/api/news');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'title', 'slug', 'content', 'publish_date', 'is_published']
                     ]
                 ]);
    }


    public function test_admin_can_create_news()
{
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin, ['*']); // Явное указание способностей

    $response = $this->postJson('/api/news', [
        'title' => 'Техническое обслуживание',
        'slug' => 'tech-service',
        'content' => 'График технического обслуживания',
        'publish_date' => now()->format('Y-m-d'),
        'is_published' => true
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.title', 'Техническое обслуживание');
}

    public function test_unauthorized_cannot_create_news()
    {
        $response = $this->postJson('/api/news', [
            'title' => 'Попытка создания'
        ]);

        $response->assertStatus(401);
    }
}
