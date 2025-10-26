<?php

namespace Tests\Feature;

use Tests\TestCase;

class WaybillCreationTest extends TestCase
{
    public function test_waybill_creation_on_activation()
    {
        // Подготовка
        $condition = RentalCondition::factory()->create([
            'shifts_per_day' => 2,
            'shift_hours' => 8,
        ]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_CONFIRMED,
            'rental_condition_id' => $condition->id,
        ]);

        $equipment = Equipment::factory()->create(['fuel_consumption' => 12.5]);
        $order->items()->create(['equipment_id' => $equipment->id]);

        // Выполнение
        $response = $this->actingAs($order->lessorCompany->admin)
            ->post(route('lessor.orders.markActive', $order));

        // Проверки
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('waybills', 2);

        $firstWaybill = Waybill::first();
        $this->assertEquals(Waybill::SHIFT_DAY, $firstWaybill->shift);
        $this->assertEquals(100, $firstWaybill->fuel_consumption_standard); // 12.5 * 8

        $secondWaybill = Waybill::latest('id')->first();
        $this->assertEquals(Waybill::SHIFT_NIGHT, $secondWaybill->shift);
    }

    public function test_activation_permissions()
    {
        // Попытка активации чужого заказа
        $order = Order::factory()->create(['status' => Order::STATUS_CONFIRMED]);
        $foreignUser = User::factory()->create();

        $response = $this->actingAs($foreignUser)
            ->post(route('lessor.orders.markActive', $order));

        $response->assertForbidden();
    }

    public function test_operator_missing_handling()
    {
        Notification::fake();

        $order = Order::factory()->create(['status' => Order::STATUS_CONFIRMED]);
        $equipment = Equipment::factory()->create(['operator_id' => null]);
        $order->items()->create(['equipment_id' => $equipment->id]);

        $this->actingAs($order->lessorCompany->admin)
            ->post(route('lessor.orders.markActive', $order));

        // Проверка отправки уведомления
        Notification::assertSentTo(
            $order->lessorCompany->managers,
            OperatorMissingNotification::class
        );

        // Проверка записи в лог
        Log::assertLogged('warning', fn ($message) => str_contains($message, 'No operator assigned')
        );
    }

    public function test_activation_date_restrictions()
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_CONFIRMED,
            'start_date' => now()->addDays(2),
        ]);

        $response = $this->actingAs($order->lessorCompany->admin)
            ->post(route('lessor.orders.markActive', $order));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('waybills', 0);
    }

    public function test_full_waybill_lifecycle()
    {
        // 1. Активация заказа
        $order = $this->activateOrderWithShifts(2);

        // 2. Получение путевых листов
        $waybills = Waybill::where('order_id', $order->id)->get();

        // 3. Заполнение данных оператором
        $this->actingAs($order->operator->user)
            ->post(route('waybill.update', $waybills[0]), [
                'odometer_start' => 1500,
                'odometer_end' => 1520,
                'fuel_start' => 50.0,
                'fuel_end' => 35.5,
                'hours_worked' => 8,
                'downtime_hours' => 0.5,
                'downtime_cause' => 'Технический перерыв',
            ]);

        // 4. Подписание клиентом
        $this->actingAs($order->lesseeCompany->admin)
            ->post(route('waybill.sign', $waybills[0]), [
                'signature' => 'svg-data...',
            ]);

        // 5. Проверка статуса и расчетов
        $waybill = $waybills[0]->fresh();
        $this->assertEquals(Waybill::STATUS_COMPLETED, $waybill->status);
        $this->assertEquals(14.5, $waybill->fuel_consumption_actual);
    }

    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
