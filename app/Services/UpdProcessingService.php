<?php

namespace App\Services;

use App\Jobs\ExportUpdTo1C;
use App\Models\Company;
use App\Models\CompletionAct;
use App\Models\ExcelMapping;
use App\Models\Order;
use App\Models\Upd;
use App\Models\UpdItem;
use App\Models\Waybill;
use App\Services\Parsers\UpdParserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdProcessingService
{
    protected $balanceService;

    protected $updParserService;

    public function __construct(BalanceService $balanceService, UpdParserService $updParserService)
    {
        $this->balanceService = $balanceService;
        $this->updParserService = $updParserService;
    }

    public function processUploadedUpd(Order $order, UploadedFile $file, array $additionalData = []): Upd
    {
        // Получаем шаблон для компании арендодателя
        $mapping = ExcelMapping::where('company_id', $order->lessor_company_id)
            ->where('type', 'upd')
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            throw new \Exception('Активный шаблон УПД не найден для компании арендодателя.');
        }

        // Получаем акт выполненных работ
        $completionAct = null;
        if (isset($additionalData['completion_act_id'])) {
            $completionAct = CompletionAct::find($additionalData['completion_act_id']);
        }

        $completionAct = CompletionAct::where('waybill_id', $additionalData['waybill_id'])
            ->where('perspective', 'lessor') // Важно: ищем акт для арендодателя
            ->first();

        if (! $completionAct) {
            // Логируем более подробную информацию для отладки
            Log::error('Акт выполненных работ не найден для путевого листа', [
                'waybill_id' => $additionalData['waybill_id'],
                'user_id' => auth()->id(),
                'existing_acts' => CompletionAct::where('waybill_id', $additionalData['waybill_id'])->get()->toArray(),
            ]);
            throw new \Exception('Акт выполненных работ не найден для данного путевого листа.');
        }

        $filePath = $file->store('temp');
        $fullPath = Storage::path($filePath);

        try {
            // Парсим УПД по конфигурации маппинга
            $parsedData = $this->updParserService->parseUpdFromExcel($fullPath, $mapping->mapping);

            // Валидируем распарсенные данные
            $this->updParserService->validateParsedData($parsedData);

            // Проверяем соответствие ИНН/КПП
            $this->validateInnKpp($parsedData, $order, $mapping);

            // Проверяем соответствие данным акта выполненных работ
            $this->validateAgainstCompletionAct($parsedData, $order, $completionAct, $mapping);

            // Проверяем уникальность УПД
            $this->checkUpdUniqueness($parsedData, $order->lessor_company_id);

            // Создаем УПД в системе
            $upd = $this->createUpdFromParsedData($order, $parsedData, $file, $additionalData);

            Log::info('УПД успешно обработан', ['upd_id' => $upd->id, 'order_id' => $order->id]);

            return $upd;

        } catch (\Exception $e) {
            Storage::delete($filePath);
            Log::error('Ошибка обработки УПД', ['error' => $e->getMessage()]);
            throw $e;
        } finally {
            Storage::delete($filePath);
        }
    }

    /**
     * Генерация упрощенного русского номера УПД
     */
    public function generateSimpleUpdNumber(): string
    {
        $currentYear = date('Y');
        $lastUpd = Upd::whereYear('created_at', $currentYear)
                    ->orderBy('id', 'desc')
                    ->first();

        $sequenceNumber = $lastUpd ? (intval(substr($lastUpd->number, -4)) + 1) : 1;

        // Упрощенный формат: ГОД/ПОСЛЕДОВАТЕЛЬНЫЙ_НОМЕР (2024/0001)
        return $currentYear . '/' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function validateAgainstCompletionAct(array $parsedData, Order $order, $completionAct, ExcelMapping $mapping): void
    {
        $header = $parsedData['header'];

        // Получаем компанию-платформу
        $platformCompany = Company::where('is_platform', true)->first();

        if (! $platformCompany) {
            throw new \Exception('Не найдена компания-платформа в системе.');
        }

        // Проверяем соответствие продавца (должен быть арендодатель)
        $sellerInn = $this->extractInnFromString($header['seller']['inn']);
        if ($sellerInn !== $order->lessorCompany->inn) {
            throw new \Exception('ИНН арендодателя в УПД не совпадает с данными в системе.');
        }

        // Определяем, кто является покупателем в этом УПД
        $isPlatformAsBuyer = false;
        if (isset($header['buyer']['inn'])) {
            $buyerInn = $this->extractInnFromString($header['buyer']['inn']);

            // Проверяем, является ли покупатель платформой
            if ($buyerInn === $platformCompany->inn) {
                $isPlatformAsBuyer = true;
            }
        }

        // Проверяем покупателя только если это не платформа
        if (! $isPlatformAsBuyer) {
            $buyerInn = $this->extractInnFromString($header['buyer']['inn']);
            if ($buyerInn !== $order->lesseeCompany->inn) {
                throw new \Exception('ИНН арендатора в УПД не совпадает с данными в системе.');
            }
        }

        // Проверяем, что в УПД указана сумма
        if (! isset($parsedData['amounts']['total']) || empty($parsedData['amounts']['total'])) {
            throw new \Exception('В УПД не указана общая сумма.');
        }

        // Проверяем суммы (допускаем расхождение до 1%) - сравниваем с актом выполненных работ
        $updTotal = (float) $parsedData['amounts']['total'];
        $completionActTotal = (float) $completionAct->total_amount;

        // Если сумма акта равна нулю, проверяем только что сумма УПД тоже ноль
        if ($completionActTotal == 0) {
            if ($updTotal != 0) {
                throw new \Exception('Сумма в УПД должна быть нулевой, так как сумма акта выполненных работ равна нулю.');
            }
        } else {
            $difference = abs($updTotal - $completionActTotal) / $completionActTotal;

            if ($difference > 0.01) {
                throw new \Exception('Сумма в УПД отличается от суммы акта выполненных работ более чем на 1%.');
            }
        }

        // Проверяем период оказания услуг (если указан в УПД)
        if (isset($parsedData['header']['service_period_start']) &&
            isset($parsedData['header']['service_period_end'])) {

            $updStart = $this->parseRussianDate($parsedData['header']['service_period_start']);
            $updEnd = $this->parseRussianDate($parsedData['header']['service_period_end']);

            // Сравниваем с периодом из акта выполненных работ (если он есть)
            if ($completionAct->start_date && $completionAct->end_date) {
                if ($updStart->format('Y-m-d') !== $completionAct->start_date->format('Y-m-d') ||
                    $updEnd->format('Y-m-d') !== $completionAct->end_date->format('Y-m-d')) {
                    throw new \Exception('Период оказания услуг в УПД не совпадает с периодом в акте выполненных работ.');
                }
            }
        }

        // Дополнительные проверки из настроек шаблона
        if (! empty($mapping->validation_rules)) {
            $this->applyCustomValidationRules($parsedData, $mapping->validation_rules);
        }
    }

    protected function checkUpdUniqueness(array $parsedData, int $lessorCompanyId): void
    {
        $number = $parsedData['header']['number'];
        $issueDateString = $parsedData['header']['issue_date'];

        try {
            // Преобразуем русскую дату в объект Carbon
            $months = [
                'января' => 'January', 'февраля' => 'February', 'марта' => 'March',
                'апреля' => 'April', 'мая' => 'May', 'июня' => 'June',
                'июля' => 'July', 'августа' => 'August', 'сентября' => 'September',
                'октября' => 'October', 'ноября' => 'November', 'декабря' => 'December',
            ];

            // Заменяем русские названия месяцев на английские
            $englishDateString = str_replace(
                array_keys($months),
                array_values($months),
                mb_strtolower($issueDateString)
            );

            // Удаляем " г." в конце строки, если есть
            $englishDateString = preg_replace('/\s*г\./', '', $englishDateString);

            // Парсим дату
            $issueDate = \Carbon\Carbon::parse($englishDateString);

            $existingUpd = Upd::where('lessor_company_id', $lessorCompanyId)
                ->where('number', $number)
                ->where('issue_date', $issueDate->format('Y-m-d'))
                ->exists();

            if ($existingUpd) {
                throw new \Exception('УПД с таким номером и датой уже существует в системе.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Неверный формат даты в УПД: '.$issueDateString);
        }
    }

    protected function createUpdFromParsedData(Order $order, array $parsedData, UploadedFile $file, array $additionalData = []): Upd
    {
        DB::beginTransaction();

        try {
            $header = $parsedData['header'];
            $amounts = $parsedData['amounts'];
            $items = $parsedData['items'] ?? [];

            // Базовые проверки
            if (!isset($additionalData['waybill_id'])) {
                throw new \Exception('Не передан waybill_id для создания УПД.');
            }

            $waybillId = $additionalData['waybill_id'];

            // Проверяем, что путевой лист существует
            $waybill = Waybill::find($waybillId);
            if (!$waybill) {
                throw new \Exception("Путевой лист #{$waybillId} не найден.");
            }

            // Проверяем, что путевой лист принадлежит заказу
            if ($waybill->order_id !== $order->id) {
                throw new \Exception("Путевой лист #{$waybillId} не принадлежит заказу #{$order->id}.");
            }

            // Проверяем, что для путевого листа еще нет УПД
            if ($waybill->upd_id) {
                throw new \Exception("Для путевого листа #{$waybillId} уже создан УПД #{$waybill->upd_id}.");
            }

            // Проверяем, что существует акт выполненных работ для этого путевого листа (для арендодателя)
            $completionAct = CompletionAct::where('waybill_id', $waybillId)
                ->where('perspective', 'lessor')
                ->first();

            if (!$completionAct) {
                throw new \Exception("Акт выполненных работ для путевого листа #{$waybillId} не найден.");
            }

            // Проверяем, что для акта еще нет УПД
            if ($completionAct->upd_id) {
                throw new \Exception("Для акта выполненных работ #{$completionAct->id} уже создан УПД #{$completionAct->upd_id}.");
            }

            // Получаем шаблон для компании арендодателя
            $mapping = ExcelMapping::where('company_id', $order->lessor_company_id)
                ->where('type', 'upd')
                ->where('is_active', true)
                ->first();

            if (!$mapping) {
                throw new \Exception('Активный шаблон УПД не найден для компании арендодателя.');
            }

            $type = Upd::TYPE_INCOMING;
            $issueDate = $this->parseRussianDate($header['issue_date']);
            $filePath = $file->store('upds', 'private');

            // ИСПРАВЛЕНИЕ: Используем упрощенную нумерацию если номер не указан в файле
            $updNumber = $header['number'] ?? $this->generateSimpleUpdNumber();

            // Проверяем уникальность номера УПД
            $existingUpd = Upd::where('number', $updNumber) // Изменено на $updNumber
                ->where('issue_date', $issueDate->format('Y-m-d'))
                ->where('lessor_company_id', $order->lessor_company_id)
                ->first();

            if ($existingUpd) {
                throw new \Exception("УПД с номером {$updNumber} и датой {$issueDate->format('d.m.Y')} уже существует.");
            }

            $updData = [
                'order_id' => $order->id,
                'lessor_company_id' => $order->lessor_company_id,
                'lessee_company_id' => $order->lessee_company_id,
                'waybill_id' => $waybillId,
                'number' => $updNumber, // Используем сгенерированный номер
                'issue_date' => $issueDate->format('Y-m-d'),
                'service_period_start' => $completionAct->service_start_date ?? $order->start_date,
                'service_period_end' => $completionAct->service_end_date ?? $order->end_date,
                'amount' => $amounts['without_vat'] ?? 0,
                'tax_amount' => $amounts['vat'] ?? 0,
                'total_amount' => $amounts['total'],
                'tax_system' => $order->lessorCompany->tax_system,
                'contract_number' => $order->contract_number,
                'contract_date' => $order->contract_date,
                'file_path' => $filePath,
                'status' => Upd::STATUS_PENDING,
                'type' => $type,
                'idempotency_key' => 'upd_' . Str::uuid(),
                'parsed_data' => $parsedData,
            ];

            $upd = Upd::create($updData);
            $this->processUpdItems($upd, $items);

            // Привязываем УПД к путевому листу
            $waybill->upd_id = $upd->id;
            $waybill->save();

            // Привязываем УПД к акту выполненных работ
            $completionAct->upd_id = $upd->id;
            $completionAct->save();

            DB::commit();

            Log::info('УПД успешно создан', [
                'upd_id' => $upd->id,
                'waybill_id' => $waybillId,
                'completion_act_id' => $completionAct->id,
                'order_id' => $order->id,
            ]);

            return $upd;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка создания УПД', [
                'error' => $e->getMessage(),
                'waybill_id' => $additionalData['waybill_id'] ?? null,
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function validateUpdForProcessing(Upd $upd): void
    {
        // ТОЛЬКО БАЗОВЫЕ ПРОВЕРКИ БЕЗ ПРОВЕРКИ АКТА

        // 1. Проверка суммы
        if ($upd->total_amount <= 0) {
            throw new \Exception('Сумма УПД должна быть больше нуля.');
        }

        // 2. Проверка существования компаний
        $upd->load(['lessorCompany', 'lesseeCompany']);

        if (! $upd->lessorCompany) {
            throw new \Exception('Компания арендодателя не найдена.');
        }

        if (! $upd->lesseeCompany) {
            throw new \Exception('Компания арендатора не найдена.');
        }

        // 3. Проверка, чтобы компания не выставляла сама себе счет
        if ($upd->lessor_company_id === $upd->lessee_company_id) {
            throw new \Exception('Компания не может выставлять УПД самой себе.');
        }
    }

    protected function parseRussianDate(string $dateString): \Carbon\Carbon
    {
        try {
            // Удаляем " г." и другие окончания года
            $cleanedDate = preg_replace('/\s*г\.?|\s*год[а]?/u', '', $dateString);

            // Разбиваем дату на части
            $dateParts = preg_split('/\s+/', trim($cleanedDate));

            if (count($dateParts) < 3) {
                throw new \Exception('Неверный формат даты: недостаточно частей');
            }

            $day = (int) $dateParts[0];
            $monthName = mb_strtolower($dateParts[1], 'UTF-8');
            $year = (int) $dateParts[2];

            // Маппинг русских названий месяцев на числовые значения
            $monthMapping = [
                'января' => 1, 'февраля' => 2, 'марта' => 3,
                'апреля' => 4, 'мая' => 5, 'июня' => 6,
                'июля' => 7, 'августа' => 8, 'сентября' => 9,
                'октября' => 10, 'ноября' => 11, 'декабря' => 12,

                // Добавляем возможные варианты написания с опечатками
                'авуста' => 8, 'августа' => 8, 'август' => 8,
            ];

            if (! isset($monthMapping[$monthName])) {
                throw new \Exception('Неизвестное название месяца: '.$monthName);
            }

            $month = $monthMapping[$monthName];

            // Создаем объект Carbon из частей даты
            return \Carbon\Carbon::create($year, $month, $day);

        } catch (\Exception $e) {
            Log::error('Ошибка парсинга даты', [
                'original' => $dateString,
                'cleaned' => $cleanedDate ?? '',
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Неверный формат даты в УПД: '.$dateString);
        }
    }

    protected function processUpdItems(Upd $upd, array $items): void
    {
        foreach ($items as $itemData) {
            // Преобразуем данные к нужным типам
            $quantity = (float) ($itemData['quantity'] ?? 0);
            $price = (float) ($itemData['price'] ?? 0);
            $amount = (float) ($itemData['amount'] ?? 0);
            $vatRate = (float) ($itemData['vat_rate'] ?? 0);
            $vatAmount = (float) ($itemData['vat_amount'] ?? 0);

            // Если сумма не указана, вычисляем ее
            if ($amount === 0.0 && $quantity > 0 && $price > 0) {
                $amount = $quantity * $price;
            }

            // Если НДС не указан, но есть ставка и сумма, вычисляем НДС
            if ($vatAmount === 0.0 && $vatRate > 0 && $amount > 0) {
                $vatAmount = $amount * ($vatRate / 100);
            }

            UpdItem::create([
                'upd_id' => $upd->id,
                'name' => $itemData['name'] ?? 'Не указано',
                'quantity' => $quantity,
                'unit' => $itemData['unit'] ?? 'шт.',
                'price' => $price,
                'amount' => $amount,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
            ]);
        }

        Log::info('Обработаны позиции УПД', [
            'upd_id' => $upd->id,
            'items_count' => count($items),
        ]);
    }

    public function acceptUpd(Upd $upd): void
    {
        if ($upd->status !== Upd::STATUS_PENDING) {
            throw new \Exception('УПД уже был обработан.');
        }

        $upd->accept();

        // Ставим задачу на экспорт в 1С
        ExportUpdTo1C::dispatch($upd);

        Log::info('УПД принят', ['upd_id' => $upd->id]);
    }

    public function rejectUpd(Upd $upd, string $reason): void
    {
        if ($upd->status !== Upd::STATUS_PENDING) {
            throw new \Exception('УПД уже был обработан.');
        }

        $upd->reject($reason);

        Log::info('УПД отклонен', ['upd_id' => $upd->id, 'reason' => $reason]);
    }

    public function verifyPaperUpd(Upd $upd, array $paperData): bool
    {
        return
            $upd->number === $paperData['number'] &&
            $upd->issue_date->format('Y-m-d') === $paperData['issue_date'] &&
            abs($upd->total_amount - $paperData['total_amount']) < 0.01;
    }

    protected function validateInnKpp(array $parsedData, Order $order, ExcelMapping $mapping): void
    {
        // Добавьте в начало метода
        \Log::debug('Validate INN/KPP', [
            'parsed_data' => $parsedData['header'],
            'lessor_inn' => $order->lessorCompany->inn,
            'lessee_inn' => $order->lesseeCompany->inn,
            'platform_inn' => Company::where('is_platform', true)->first()->inn ?? 'not_found',
        ]);

        $header = $parsedData['header'];

        // Получаем компанию-платформу
        $platformCompany = Company::where('is_platform', true)->first();

        if (! $platformCompany) {
            throw new \Exception('Не найдена компания-платформа в системе.');
        }

        // Проверяем ИНН продавца (должен быть арендодатель)
        if (isset($header['seller']['inn'])) {
            $sellerInn = $this->extractInnFromString($header['seller']['inn']);
            $this->validateInn($sellerInn, $order->lessorCompany->inn, 'продавца');
        }

        // Проверяем КПП продавца, если он есть в шаблоне
        if (isset($header['seller']['kpp']) && ! empty($order->lessorCompany->kpp)) {
            $sellerKpp = $this->extractKppFromString($header['seller']['kpp']);
            $this->validateKpp($sellerKpp, $order->lessorCompany->kpp, 'продавца');
        }

        // Определяем, кто является покупателем в этом УПД
        $isPlatformAsBuyer = false;
        if (isset($header['buyer']['inn'])) {
            $buyerInn = $this->extractInnFromString($header['buyer']['inn']);

            // Проверяем, является ли покупатель платформой
            if ($buyerInn === $platformCompany->inn) {
                $isPlatformAsBuyer = true;
            }
        }

        // В зависимости от того, кто покупатель, выполняем соответствующую проверку
        if ($isPlatformAsBuyer) {
            // Покупатель - платформа
            if (isset($header['buyer']['inn'])) {
                $buyerInn = $this->extractInnFromString($header['buyer']['inn']);
                $this->validateInn($buyerInn, $platformCompany->inn, 'покупателя (платформы)');
            }

            if (isset($header['buyer']['kpp']) && ! empty($platformCompany->kpp)) {
                $buyerKpp = $this->extractKppFromString($header['buyer']['kpp']);
                $this->validateKpp($buyerKpp, $platformCompany->kpp, 'покупателя (платформы)');
            }
        } else {
            // Покупатель - арендатор
            if (isset($header['buyer']['inn'])) {
                $buyerInn = $this->extractInnFromString($header['buyer']['inn']);
                $this->validateInn($buyerInn, $order->lesseeCompany->inn, 'покупателя');
            }

            if (isset($header['buyer']['kpp']) && ! empty($order->lesseeCompany->kpp)) {
                $buyerKpp = $this->extractKppFromString($header['buyer']['kpp']);
                $this->validateKpp($buyerKpp, $order->lesseeCompany->kpp, 'покупателя');
            }
        }
    }

    protected function extractInnFromString($value): string
    {
        if (is_string($value)) {
            // Удаляем все нецифровые символы, кроме слеша
            $value = preg_replace('/[^0-9\/]/', '', $value);

            // Если есть слеш, берем часть до него
            if (strpos($value, '/') !== false) {
                $parts = explode('/', $value);

                return trim($parts[0]);
            }

            // Если значение длиннее 10 символов, берем первые 10 (для ИНН)
            if (strlen($value) > 10) {
                return substr($value, 0, 10);
            }
        }

        return (string) $value;
    }

    protected function extractKppFromString($value): string
    {
        if (is_string($value)) {
            // Удаляем все нецифровые символы, кроме слеша
            $value = preg_replace('/[^0-9\/]/', '', $value);

            // Если есть слеш, берем часть после него
            if (strpos($value, '/') !== false) {
                $parts = explode('/', $value);

                return count($parts) > 1 ? trim($parts[1]) : '';
            }

            // Если значение длиннее 9 символов, берем последние 9 (для КПП)
            if (strlen($value) > 9) {
                return substr($value, -9);
            }
        }

        return (string) $value;
    }

    protected function validateInn($documentInn, string $companyInn, string $entity): void
    {
        $documentInn = $this->extractInnFromString($documentInn);

        if ($documentInn !== $companyInn) {
            throw new \Exception("ИНН {$entity} в УПД ({$documentInn}) не совпадает с ИНН в системе ({$companyInn}).");
        }
    }

    protected function validateKpp($documentKpp, string $companyKpp, string $entity): void
    {
        $documentKpp = $this->extractKppFromString($documentKpp);

        if (! empty($companyKpp) && $documentKpp !== $companyKpp) {
            throw new \Exception("КПП {$entity} в УПД ({$documentKpp}) не совпадает с КПП в системе ({$companyKpp}).");
        }
    }
}
