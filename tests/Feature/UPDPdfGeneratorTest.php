<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\OrderItem;
use App\Services\UPDPdfGenerator;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class UPDPdfGeneratorTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Отключаем обработку PDF в тестах
        PDF::swap(new class {
            public function loadView($view, $data = []) {
                return new class($view, $data) {
                    public function download($filename) {
                        return response()->make('PDF_CONTENT', 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'attachment; filename="'.$filename.'"'
                        ]);
                    }

                    public function setPaper($size, $orientation) {}
                    public function setOption($option, $value) {}
                };
            }
        });
    }

    /** @test */
    public function it_generates_upd_for_lessor()
    {
        // Создаем тестовые данные
        $platform = Platform::factory()->create();
        $lessor = Company::factory()->create(['type' => 'lessor']);
        $lessee = Company::factory()->create(['type' => 'lessee']);

        $order = Order::factory()->create([
            'lessor_company_id' => $lessor->id,
            'lessee_company_id' => $lessee->id,
            'base_amount' => 10000,
        ]);

        $equipment = Equipment::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'equipment_id' => $equipment->id,
            'base_price' => 5000,
            'period_count' => 2
        ]);

        // Генерируем PDF
        $generator = new UPDPdfGenerator();
        $response = $generator->generateForLessor($order);

        // Проверяем ответ
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals(
            'attachment; filename="УПД_'.$order->id.'_арендодатель.pdf"',
            $response->headers->get('Content-Disposition')
        );
        $this->assertEquals('PDF_CONTENT', $response->getContent());
    }

    /** @test */
    public function it_generates_upd_for_lessee()
    {
        // Аналогично для арендатора
        $platform = Platform::factory()->create();
        $lessor = Company::factory()->create(['type' => 'lessor']);
        $lessee = Company::factory()->create(['type' => 'lessee']);

        $order = Order::factory()->create([
            'lessor_company_id' => $lessor->id,
            'lessee_company_id' => $lessee->id,
        ]);

        $generator = new UPDPdfGenerator();
        $response = $generator->generateForLessee($order);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'attachment; filename="УПД_'.$order->id.'_арендатор.pdf"',
            $response->headers->get('Content-Disposition')
        );
    }

    /** @test */
    public function it_handles_missing_platform_data()
    {
        // Убедимся, что нет данных платформы
        Platform::query()->delete();

        $order = Order::factory()->create();
        $generator = new UPDPdfGenerator();

        $response = $generator->generateForLessor($order);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('PDF_CONTENT', $response->getContent());
    }

    /** @test */
    public function it_handles_empty_order()
    {
        $order = Order::factory()->create(['base_amount' => 0]);
        $generator = new UPDPdfGenerator();

        $response = $generator->generateForLessor($order);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
