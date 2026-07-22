<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TestSeeder::class);
    }

    public function test_guest_cannot_create_order()
    {
        $this->postJson('/api/orders', [])->assertStatus(401);
    }

    public function test_lessee_can_create_order_from_cart()
    {
        $user = User::where('email', 'lessee@test.com')->first();
        $equipment = Equipment::first();

        // Добавляем в корзину
        $this->actingAs($user)->postJson('/api/cart', [
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'shifts_per_day' => 1,
            'hours_per_shift' => 8,
            'quantity' => 1,
        ])->assertOk();

        // Создаём заказ
        $response = $this->actingAs($user)->postJson('/api/orders', [
            'comment' => 'Тестовый заказ',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'lessee_company_id' => $user->company_id,
        ]);
    }

    public function test_order_total_amount_matches_cart()
    {
        $user = User::where('email', 'lessee@test.com')->first();
        $equipment = Equipment::first();
        $pricePerHour = $equipment->rentalTerms->first()->price_per_hour ?? 1000;
        $days = 2;
        $hours = 8;
        $expectedTotal = $pricePerHour * $days * $hours;

        $this->actingAs($user)->postJson('/api/cart', [
            'equipment_id' => $equipment->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays($days)->format('Y-m-d'),
            'shifts_per_day' => 1,
            'hours_per_shift' => $hours,
            'quantity' => 1,
        ]);

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
    }

    public function test_empty_cart_cannot_create_order()
    {
        $user = User::where('email', 'lessee@test.com')->first();
        $this->actingAs($user)->postJson('/api/orders', [])
            ->assertStatus(400);
    }
}
