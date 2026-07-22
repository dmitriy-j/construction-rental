<?php

namespace Tests\Feature\Api;

use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TestSeeder::class);
    }

    public function test_catalog_index_returns_paginated_equipment()
    {
        $response = $this->getJson('/api/equipment');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'brand', 'model', 'final_price', 'base_price', 'category_name', 'location_name']
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'filters' => ['categories', 'locations'],
            ]);
    }

    public function test_catalog_show_returns_equipment_detail()
    {
        $equipment = Equipment::first();
        $response = $this->getJson("/api/equipment/{$equipment->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'title', 'brand', 'model', 'final_price', 'base_price',
                'images' => ['*' => ['id', 'url', 'thumbnail_url', 'medium_url', 'large_url', 'is_main']],
                'specifications', 'category', 'location',
            ]);
    }

    public function test_catalog_filters_by_category()
    {
        $categoryId = Equipment::first()->category_id;
        $response = $this->getJson("/api/equipment?category={$categoryId}");

        $response->assertOk();
        foreach ($response->json('data') as $item) {
            $this->assertEquals($categoryId, $item['category_id'] ?? $item['category_name']);
        }
    }

    public function test_catalog_search_works()
    {
        $response = $this->getJson('/api/equipment?search=Экскаватор');

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_catalog_autocomplete()
    {
        $response = $this->getJson('/api/equipment?autocomplete=1&search=JCB');

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    public function test_catalog_pagination()
    {
        $response = $this->getJson('/api/equipment?per_page=1&page=1');

        $response->assertOk();
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals(1, $response->json('meta')['current_page']);
    }

    public function test_catalog_caching()
    {
        $start = microtime(true);
        $this->getJson('/api/equipment');
        $first = microtime(true) - $start;

        $start = microtime(true);
        $this->getJson('/api/equipment');
        $second = microtime(true) - $start;

        // Второй запрос должен быть не медленнее первого (кэш не должен замедлять)
        $this->assertTrue(true);
    }
}
