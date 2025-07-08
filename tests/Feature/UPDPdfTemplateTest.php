<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UPDPdfTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_correct_order_data_in_template()
    {
        // Создаем тестовые данные
        $platform = Platform::factory()->create([
            'name' => 'Тестовая платформа',
            'inn' => '1234567890',
            'kpp' => '123456789',
            'legal_address' => 'г. Москва, ул. Тестовая, д. 1',
            'ceo_name' => 'Иванов И.И.',
            'accountant_name' => 'Петрова П.П.'
        ]);

        $lessor = Company::factory()->create([
            'name' => 'Арендодатель ООО',
            'inn' => '0987654321',
            'kpp' => '987654321',
            'legal_address' => 'г. Санкт-Петербург, ул. Арендная, д. 10',
            'type' => 'lessor'
        ]);

        $lessee = Company::factory()->create([
            'name' => 'Арендатор ООО',
            'inn' => '1122334455',
            'kpp' => '667788990',
            'legal_address' => 'г. Екатеринбург, ул. Заказчиков, д. 5',
            'type' => 'lessee'
        ]);

        $order = Order::factory()->create([
            'id' => 1001,
            'contract_number' => 'Д-100',
            'contract_date' => now(),
            'delivery_date' => now()->addDays(5),
            'shipping_number' => 'ТН-001',
            'payment_number' => 'ПП-001',
            'base_amount' => 15000.50,
            'lessor_company_id' => $lessor->id,
            'lessee_company_id' => $lessee->id
        ]);

        $equipment = Equipment::factory()->create(['name' => 'Экскаватор CAT-330']);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'equipment_id' => $equipment->id,
            'base_price' => 7500.25,
            'period_count' => 2
        ]);

        // Рендерим шаблон
        $view = view('pdf.upd', [
            'order' => $order,
            'platform' => $platform,
            'counterparty' => $lessor,
            'type' => 'lessor'
        ]);

        $html = $view->render();

        // Проверяем наличие ключевых данных
        $this->assertStringContainsString('Универсальный передаточный документ', $html);
        $this->assertStringContainsString('Тестовая платформа', $html);
        $this->assertStringContainsString('1234567890', $html);
        $this->assertStringContainsString('Арендодатель ООО', $html);
        $this->assertStringContainsString('1001', $html);
        $this->assertStringContainsString('Экскаватор CAT-330', $html);
        $this->assertStringContainsString('15 000,50', $html);
        $this->assertStringContainsString('Д-100', $html);
        $this->assertStringContainsString('Иванов И.И.', $html);
        $this->assertStringContainsString('Петрова П.П.', $html);
    }

    /** @test */
    public function it_handles_missing_optional_fields()
    {
        $platform = Platform::factory()->create([
            'accountant_name' => null,
            'kpp' => null
        ]);

        $order = Order::factory()->create([
            'contract_number' => null,
            'shipping_number' => null
        ]);

        $view = view('pdf.upd', [
            'order' => $order,
            'platform' => $platform,
            'counterparty' => Company::factory()->create(),
            'type' => 'lessor'
        ]);

        $html = $view->render();

        $this->assertStringNotContainsString('Главный бухгалтер', $html);
        $this->assertStringContainsString('--', $html);
    }

    /** @test */
    public function it_formats_numbers_correctly()
    {
        $order = Order::factory()->create([
            'base_amount' => 12345.67
        ]);

        $equipment = Equipment::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'equipment_id' => $equipment->id,
            'base_price' => 1234.56,
            'period_count' => 3
        ]);

        $view = view('pdf.upd', [
            'order' => $order,
            'platform' => Platform::factory()->create(),
            'counterparty' => Company::factory()->create(),
            'type' => 'lessor'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('12 345,67', $html);
        $this->assertStringContainsString('1 234,56', $html);
        $this->assertStringContainsString('3 703,68', $html);
    }
}
