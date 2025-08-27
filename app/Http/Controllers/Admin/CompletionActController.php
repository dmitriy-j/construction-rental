<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompletionAct;
use App\Models\Upd;
use App\Models\UpdItem;
use App\Models\Company;
use App\Models\Contract;
use App\Models\DeliveryNote;
use App\Models\Waybill;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\TaxHelper;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;

class CompletionActController extends Controller
{
    public function generateUpd(CompletionAct $completionAct)
    {
        try {
            $upd = $this->generateUpdInternal($completionAct);

            return redirect()->route('admin.upds.show', $upd)
                ->with('success', 'УПД успешно создан для арендатора');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка при создании УПД: ' . $e->getMessage());
        }
    }

    // Массовая генерация УПД
   public function generateUpdForAll()
{
    \Log::info('Запуск массовой генерации УПД для арендаторов');

    try {
        // Находим все акты для арендаторов без созданных УПД
        $completionActs = CompletionAct::where('perspective', 'lessee')
            ->whereNull('upd_id')
            ->get();

        \Log::info("Найдено актов для обработки: " . $completionActs->count());

        $generatedCount = 0;
        $errors = [];

        foreach ($completionActs as $index => $completionAct) {
            try {
                \Log::info("Обработка акта #{$completionAct->id} ({$index}/{$completionActs->count()})");

                // Генерируем УПД для каждого акта
                $upd = $this->generateUpdInternal($completionAct);
                $generatedCount++;

                \Log::info("Успешно создан УПД #{$upd->id} для акта #{$completionAct->id}");

            } catch (\Exception $e) {
                // Логируем ошибку, но продолжаем обработку остальных актов
                $errorMsg = "Акт #{$completionAct->id}: " . $e->getMessage();
                $errors[] = $errorMsg;
                \Log::error("Ошибка генерации УПД для акта #{$completionAct->id}: " . $e->getMessage(), [
                    'exception' => $e,
                    'act_data' => $completionAct->toArray()
                ]);
            }
        }

        $message = "Успешно создано УПД для {$generatedCount} актов.";

        if (!empty($errors)) {
            $errorCount = count($errors);
            $message .= " Не удалось создать для {$errorCount} актов. Подробности в логах.";

            // Записываем все ошибки в лог одной записью для удобства
            \Log::error("Ошибки массовой генерации УПД:", [
                'total_errors' => $errorCount,
                'errors' => $errors
            ]);
        }

        \Log::info("Завершение массовой генерации УПД. Результат: {$message}");

        return redirect()->route('admin.documents.index', ['type' => 'completion_acts'])
            ->with('success', $message);

    } catch (\Exception $e) {
        \Log::error("Критическая ошибка в массовой генерации УПД: " . $e->getMessage(), [
            'exception' => $e
        ]);

        return redirect()->back()
            ->with('error', 'Критическая ошибка при массовой генерации УПД: ' . $e->getMessage());
    }
}

protected function generateUpdInternal(CompletionAct $completionAct)
{
    \Log::debug("Начало генерации УПД для акта #{$completionAct->id}");

    // Проверки
    if ($completionAct->perspective !== 'lessee') {
        throw new \Exception('УПД можно генерировать только для актов, предназначенных арендаторам');
    }

    if ($completionAct->upd) {
        throw new \Exception('УПД для этого акта уже создан');
    }

    // Получаем компании
    $platformCompany = Company::where('is_platform', true)->first();

    if (!$platformCompany) {
        throw new \Exception('Не найдена компания-платформа');
    }

    // Определяем компанию арендатора из родительского заказа
    $order = $completionAct->order;
    $parentOrder = $order->parentOrder;

    if (!$parentOrder) {
        throw new \Exception('Не найден родительский заказ для определения арендатора');
    }

    $lesseeCompany = $parentOrder->lesseeCompany;

    if (!$lesseeCompany) {
        throw new \Exception('Не удалось определить компанию арендатора');
    }

    \Log::debug("Определены компании: платформа={$platformCompany->id}, арендатор={$lesseeCompany->id}");

    // Получаем данные для позиции УПД
    $orderItem = $order->items()->first();

    if (!$orderItem) {
        throw new \Exception('Не найдены позиции в заказе');
    }

    $equipment = $orderItem->equipment;

    if (!$equipment) {
        throw new \Exception('Не найдено оборудование для позиции заказа');
    }

    // Находим путевой лист для получения гос. номера
    $waybill = $completionAct->waybill;
    $licensePlate = $waybill ? $waybill->license_plate : 'не указан';

    // Формируем наименование услуги
    $serviceName = sprintf(
        'Аренда %s (гос. номер: %s) за период %s - %s',
        $equipment->title,
        $licensePlate,
        $completionAct->service_start_date->format('d.m.Y'),
        $completionAct->service_end_date->format('d.m.Y')
    );

    \Log::debug("Сформировано наименование услуги: {$serviceName}");

    // Определяем ставку НДС платформы
    $vatRate = ($platformCompany->tax_system === 'vat') ? 20.0 : 0.0;

    // ПРАВИЛЬНЫЙ РАСЧЕТ НДС
    $priceWithVat = $orderItem->price_per_unit; // Цена с НДС (2310)
    $priceWithoutVat = $priceWithVat / (1 + $vatRate/100); // Цена без НДС (1925)
    $amountWithoutVat = $priceWithoutVat * $completionAct->total_hours; // Сумма без НДС (57750)
    $vatAmount = $amountWithoutVat * ($vatRate/100); // Сумма НДС (11550)
    $totalAmount = $amountWithoutVat + $vatAmount; // Итоговая сумма с НДС (69300)

    // Округляем все значения до 2 знаков
    $priceWithoutVat = round($priceWithoutVat, 2);
    $amountWithoutVat = round($amountWithoutVat, 2);
    $vatAmount = round($vatAmount, 2);
    $totalAmount = round($totalAmount, 2);

    \Log::debug("Рассчитаны финансовые показатели: цена={$priceWithoutVat}, сумма={$amountWithoutVat}, НДС={$vatAmount}, итого={$totalAmount}");

    // Генерация номера УПД
    $number = 'УПД-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    \Log::debug("Сгенерирован номер УПД: {$number}");

    // Создаем УПД (ПЛАТФОРМА → АРЕНДАТОР)
    $upd = Upd::create([
        'order_id' => $completionAct->order_id,
        'parent_order_id' => $parentOrder->id, // Сохраняем ссылку на родительский заказ
        'lessor_company_id' => $platformCompany->id, // Продавец - платформа
        'lessee_company_id' => $lesseeCompany->id,   // Покупатель - арендатор
        'number' => $number,
        'issue_date' => now(),
        'service_period_start' => $completionAct->service_start_date,
        'service_period_end' => $completionAct->service_end_date,
        'amount' => $amountWithoutVat,
        'tax_amount' => $vatAmount,
        'total_amount' => $totalAmount,
        'tax_system' => $platformCompany->tax_system,
        'contract_number' => $order->contract_number,
        'contract_date' => $order->contract_date,
        'status' => 'pending',
        'perspective' => 'lessee',
        // Добавляем реквизиты платформы для автоматической подстановки
        'lessor_sign_position' => 'Генеральный директор',
        'lessor_sign_name' => 'Иванов И.И.',
        'lessor_sign_date' => now(),
    ]);

    \Log::debug("Создан УПД в базе данных: #{$upd->id}");

    // Создаем позицию УПД
    UpdItem::create([
        'upd_id' => $upd->id,
        'name' => $serviceName,
        'quantity' => $completionAct->total_hours,
        'unit' => 'час',
        'price' => $priceWithoutVat, // Цена без НДС
        'amount' => $amountWithoutVat, // Сумма без НДС
        'vat_rate' => $vatRate,
        'vat_amount' => $vatAmount,
    ]);

    \Log::debug("Создана позиция УПД для акта #{$completionAct->id}");

    // Связываем акт с УПД
    $completionAct->update(['upd_id' => $upd->id]);
    \Log::debug("Акт #{$completionAct->id} связан с УПД #{$upd->id}");

    \Log::info("Успешно завершена генерация УПД #{$upd->id} для акта #{$completionAct->id}");

    return $upd;
}

    private function validateActForUpd(CompletionAct $act)
    {
        if (!$act->order) {
            throw new \Exception('Не найден заказ для акта');
        }

        if (!$act->order->lesseeCompany) {
            throw new \Exception('Не найдена компания арендатора');
        }

        if ($act->total_amount <= 0) {
            throw new \Exception('Сумма акта должна быть больше нуля');
        }
    }
}
