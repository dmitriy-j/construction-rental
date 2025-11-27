<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompletionAct;
use App\Models\Upd;
use App\Models\UpdItem;
use App\Services\UpdProcessingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompletionActController extends Controller
{
    public function generateUpd(CompletionAct $completionAct)
    {
        try {
            // Проверяем, что у акта нет УПД
            if ($completionAct->upd_id) {
                return redirect()->back()
                    ->with('error', 'Для этого акта уже создан УПД');
            }

            // Проверяем, что у связанного путевого листа нет УПД
            if ($completionAct->waybill && $completionAct->waybill->upd_id) {
                return redirect()->back()
                    ->with('error', 'Для путевого листа этого акта уже создан УПД');
            }

            $upd = $this->generateUpdInternal($completionAct);

            return redirect()->route('admin.upds.show', $upd)
                ->with('success', 'УПД успешно создан для арендатора');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка при создании УПД: '.$e->getMessage());
        }
    }

    // Массовая генерация УПД
    public function generateUpdForAll()
    {
        \Log::info('Запуск массовой генерации УПД для арендаторов с транзакционной блокировкой');

        try {
            // Получаем ID актов без блокировки для первоначального отбора
            $completionActIds = CompletionAct::where('perspective', 'lessee')
                ->whereNull('upd_id')
                ->pluck('id');

            \Log::info('Найдено актов для обработки: '.$completionActIds->count());

            $generatedCount = 0;
            $errors = [];

            foreach ($completionActIds as $actId) {
                DB::beginTransaction();

                try {
                    // Блокируем конкретный акт для изменения
                    $completionAct = CompletionAct::where('id', $actId)
                        ->lockForUpdate()
                        ->first();

                    if (! $completionAct) {
                        DB::rollBack();
                        continue;
                    }

                    // Перезагружаем отношения с блокировкой
                    $completionAct->load(['waybill' => function ($query) {
                        $query->lockForUpdate();
                    }, 'order.parentOrder.lesseeCompany']);

                    \Log::info("Обработка акта #{$completionAct->id}");

                    // Атомарная проверка условий внутри транзакции
                    if ($completionAct->upd_id) {
                        throw new \Exception('Акт уже имеет привязанный УПД');
                    }

                    if (! $completionAct->waybill) {
                        throw new \Exception('Акт не имеет путевого листа');
                    }

                    if ($completionAct->waybill->upd_id) {
                        throw new \Exception('Путевой лист уже имеет привязанный УПД');
                    }

                    // Генерируем УПД
                    $upd = $this->generateUpdInternal($completionAct);
                    $generatedCount++;

                    DB::commit();

                    \Log::info("Успешно создан УПД #{$upd->id} для акта #{$completionAct->id}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    $errorMsg = "Акт #{$actId}: ".$e->getMessage();
                    $errors[] = $errorMsg;
                    \Log::error("Ошибка генерации УПД для акта #{$actId}: ".$e->getMessage());
                }
            }

            $message = "Успешно создано УПД для {$generatedCount} актов.";

            if (! empty($errors)) {
                $errorCount = count($errors);
                $message .= " Не удалось создать для {$errorCount} актов. Подробности в логах.";

                \Log::error('Ошибки массовой генерации УПД:', [
                    'total_errors' => $errorCount,
                    'errors' => $errors,
                ]);
            }

            \Log::info("Завершение массовой генерации УПД. Результат: {$message}");

            return redirect()->route('admin.documents.index', ['type' => 'completion_acts'])
                ->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Критическая ошибка в массовой генерации УПД: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Критическая ошибка при массовой генерации УПД: '.$e->getMessage());
        }
    }

    protected function generateUpdInternal(CompletionAct $completionAct)
    {
        \Log::debug("Начало генерации УПД для акта #{$completionAct->id}");

        // Проверки внутри транзакции для атомарности
        DB::beginTransaction();

        try {
            // Перезагружаем модель с актуальными данными
            $completionAct->refresh();

            // Проверки
            if ($completionAct->perspective !== 'lessee') {
                throw new \Exception('УПД можно генерировать только для актов, предназначенных арендаторам');
            }

            if ($completionAct->upd_id) {
                throw new \Exception('УПД для этого акта уже создан');
            }

            // Проверка, что путевой лист существует и не имеет УПД
            if (! $completionAct->waybill) {
                throw new \Exception('Не найден путевой лист для акта');
            }

            // Перезагружаем путевой лист для актуальных данных
            $waybill = $completionAct->waybill;
            $waybill->refresh();

            if ($waybill->upd_id) {
                throw new \Exception('Для путевого листа уже создан УПД');
            }

            // Получаем компании
            $platformCompany = Company::where('is_platform', true)->first();

            if (! $platformCompany) {
                throw new \Exception('Не найдена компания-платформа');
            }

            // Определяем компанию арендатора из родительского заказа
            $order = $completionAct->order;
            $parentOrder = $order->parentOrder;

            if (! $parentOrder) {
                throw new \Exception('Не найден родительский заказ для определения арендатора');
            }

            $lesseeCompany = $parentOrder->lesseeCompany;

            if (! $lesseeCompany) {
                throw new \Exception('Не удалось определить компанию арендатора');
            }

            \Log::debug("Определены компании: платформа={$platformCompany->id}, арендатор={$lesseeCompany->id}");

            // Получаем данные для позиции УПД
            $orderItem = $order->items()->first();

            if (! $orderItem) {
                throw new \Exception('Не найдены позиции в заказе');
            }

            $equipment = $orderItem->equipment;

            if (! $equipment) {
                throw new \Exception('Не найдено оборудование для позиции заказа');
            }

            // Находим путевой лист для получения гос. номера
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
            $priceWithoutVat = $priceWithVat / (1 + $vatRate / 100); // Цена без НДС (1925)
            $amountWithoutVat = $priceWithoutVat * $completionAct->total_hours; // Сумма без НДС (57750)
            $vatAmount = $amountWithoutVat * ($vatRate / 100); // Сумма НДС (11550)
            $totalAmount = $amountWithoutVat + $vatAmount; // Итоговая сумма с НДС (69300)

            // Округляем все значения до 2 знаков
            $priceWithoutVat = round($priceWithoutVat, 2);
            $amountWithoutVat = round($amountWithoutVat, 2);
            $vatAmount = round($vatAmount, 2);
            $totalAmount = round($totalAmount, 2);

            \Log::debug("Рассчитаны финансовые показатели: цена={$priceWithoutVat}, сумма={$amountWithoutVat}, НДС={$vatAmount}, итого={$totalAmount}");

            // Генерация номера УПД через сервис - ИСПРАВЛЕННАЯ ЧАСТЬ
            $updProcessingService = app(UpdProcessingService::class);
            $number = $updProcessingService->generateUpdNumber();
            \Log::debug("Сгенерирован номер УПД в новом формате: {$number}");

            // Создаем УПД (ПЛАТФОРМА → АРЕНДАТОР)
            $upd = Upd::create([
                'order_id' => $completionAct->order_id,
                'parent_order_id' => $parentOrder->id,
                'lessor_company_id' => $platformCompany->id,
                'lessee_company_id' => $lesseeCompany->id,
                'waybill_id' => $waybill->id,
                'number' => $number, // Используем новый формат номера
                'issue_date' => now(),
                'service_period_start' => $completionAct->service_start_date,
                'service_period_end' => $completionAct->service_end_date,
                'amount' => $amountWithoutVat,
                'tax_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'tax_system' => $platformCompany->tax_system,
                'contract_number' => $order->contract_number,
                'contract_date' => $order->contract_date,
                'type' => Upd::TYPE_OUTGOING,
                'status' => 'pending',
                'perspective' => 'lessee',
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
                'price' => $priceWithoutVat,
                'amount' => $amountWithoutVat,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
            ]);

            \Log::debug("Создана позиция УПД для акта #{$completionAct->id}");

            // Связываем акт с УПД
            $completionAct->upd_id = $upd->id;
            $completionAct->save();

            // Связываем путевой лист с УПД
            $waybill->upd_id = $upd->id;
            $waybill->save();

            DB::commit();

            \Log::debug("Акт #{$completionAct->id} и путевой лист #{$waybill->id} связаны с УПД #{$upd->id}");
            \Log::info("Успешно завершена генерации УПД #{$upd->id} для акта #{$completionAct->id}");

            return $upd;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Ошибка генерации УПД для акта #{$completionAct->id}: ".$e->getMessage());
            throw $e;
        }
    }

    private function validateActForUpd(CompletionAct $act)
    {
        if (! $act->order) {
            throw new \Exception('Не найден заказ для акта');
        }

        if (! $act->order->lesseeCompany) {
            throw new \Exception('Не найдена компания арендатора');
        }

        if ($act->total_amount <= 0) {
            throw new \Exception('Сумма акта должна быть больше нуля');
        }
    }
}
