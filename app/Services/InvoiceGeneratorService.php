<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Upd;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceGeneratorService
{
    protected $documentGeneratorService;

    public function __construct(DocumentGeneratorService $documentGeneratorService)
    {
        $this->documentGeneratorService = $documentGeneratorService;
    }

    /**
     * Генерация счета
     */
    public function generateInvoice(Order $order, string $scenario, Upd $upd = null, array $templateData = [])
    {
        try {
            Log::info('Генерация счета', [
                'order_id' => $order->id,
                'scenario' => $scenario,
                'upd_id' => $upd?->id
            ]);

            // Находим подходящий шаблон
            $template = $this->findTemplateByScenario($scenario);

            if (!$template) {
                throw new \Exception("Шаблон для сценария {$scenario} не найден");
            }

            // Подготавливаем данные для шаблона
            $invoiceData = $this->prepareInvoiceData($order, $scenario, $upd, $templateData);

            // Создаем запись счета в базе данных
            $invoice = $this->createInvoiceRecord($order, $scenario, $invoiceData, $upd);

            // Генерируем документ
            $filePath = $this->documentGeneratorService->generateDocument($template, $invoiceData);

            // Обновляем путь к файлу счета
            $invoice->update(['file_path' => $filePath]);

            // Обновляем статус счета на "отправлен"
            $invoice->update(['status' => Invoice::STATUS_SENT]);

            Log::info('Счет успешно сгенерирован', [
                'invoice_id' => $invoice->id,
                'file_path' => $filePath,
                'number' => $invoice->number
            ]);

            return [
                'invoice' => $invoice,
                'file_path' => $filePath
            ];

        } catch (\Exception $e) {
            Log::error('Ошибка генерации счета', [
                'order_id' => $order->id,
                'scenario' => $scenario,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Генерация авансового счета к заказу
     */
    public function generateAdvanceInvoice(Order $order, float $advanceAmount = null)
    {
        $advanceAmount = $advanceAmount ?? $this->calculateAdvanceAmount($order);

        return $this->generateInvoice($order, DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER, null, [
            'invoice' => [
                'is_advance' => true,
                'advance_amount' => $advanceAmount,
                'due_date' => $this->calculateDueDate('advance_order'),
            ]
        ]);
    }

    /**
     * Генерация постоплатного счета к УПД
     */
    public function generatePostpaymentInvoice(Upd $upd)
    {
        $order = $upd->order;

        return $this->generateInvoice($order, DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD, $upd, [
            'invoice' => [
                'is_postpayment' => true,
                'due_date' => $this->calculateDueDate('postpayment_upd'),
            ]
        ]);
    }

    /**
     * Генерация счета на частичную оплату
     */
    public function generatePartialPaymentInvoice(Order $order, float $percentage = 30)
    {
        $partialAmount = $order->total_amount * ($percentage / 100);

        return $this->generateInvoice($order, DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT, null, [
            'invoice' => [
                'is_partial' => true,
                'partial_percentage' => $percentage,
                'partial_amount' => $partialAmount,
                'due_date' => $this->calculateDueDate('partial_payment'),
            ]
        ]);
    }

    /**
     * Генерация корректировочного счета
     */
    public function generateCorrectionInvoice(Invoice $originalInvoice, array $correctionData)
    {
        $order = $originalInvoice->order;

        return $this->generateInvoice($order, DocumentTemplate::INVOICE_SCENARIO_CORRECTION, null, array_merge([
            'invoice' => [
                'is_correction' => true,
                'original_invoice_number' => $originalInvoice->number,
                'original_invoice_date' => $originalInvoice->issue_date->format('d.m.Y'),
                'correction_reason' => $correctionData['reason'] ?? '',
                'due_date' => $this->calculateDueDate('correction'),
            ]
        ], $correctionData));
    }

    /**
     * Поиск шаблона по сценарию
     */
    protected function findTemplateByScenario(string $scenario): ?DocumentTemplate
    {
        return DocumentTemplate::active()
            ->byType(DocumentTemplate::TYPE_INVOICE)
            ->byScenario($scenario)
            ->first();
    }

    /**
     * Подготовка данных для счета
     */
    protected function prepareInvoiceData(Order $order, string $scenario, Upd $upd = null, array $templateData = []): array
    {
        $baseData = [
            'invoice' => [
                'number' => $this->generateSimpleInvoiceNumber($order, $scenario), // Используем упрощенную нумерацию
                'date' => now()->format('d.m.Y'),
                'scenario' => $scenario,
                'scenario_name' => $this->getScenarioName($scenario),
                'due_date' => $this->calculateDueDate($scenario),
            ],
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'date' => $order->created_at->format('d.m.Y'),
                'start_date' => $order->start_date?->format('d.m.Y'),
                'end_date' => $order->end_date?->format('d.m.Y'),
                'total_amount' => $order->total_amount,
                'advance_amount' => $order->advance_amount ?? 0,
                'remaining_amount' => $order->total_amount - ($order->advance_amount ?? 0),
                'contract_number' => $order->contract_number,
                'contract_date' => $order->contract_date?->format('d.m.Y'),
            ],
            'customer' => [
                'name' => $order->customer->name ?? '',
                'legal_name' => $order->customer->legal_name ?? '',
                'inn' => $order->customer->inn ?? '',
                'kpp' => $order->customer->kpp ?? '',
                'address' => $order->customer->address ?? '',
                'bank_account' => $order->customer->bank_account ?? '',
                'bank_name' => $order->customer->bank_name ?? '',
                'bik' => $order->customer->bik ?? '',
                'correspondent_account' => $order->customer->correspondent_account ?? '',
            ],
            'lessor_company' => [
                'name' => $order->lessorCompany->name,
                'legal_name' => $order->lessorCompany->legal_name,
                'inn' => $order->lessorCompany->inn,
                'kpp' => $order->lessorCompany->kpp,
                'address' => $order->lessorCompany->address,
                'bank_account' => $order->lessorCompany->bank_account,
                'bank_name' => $order->lessorCompany->bank_name,
                'bik' => $order->lessorCompany->bik,
                'correspondent_account' => $order->lessorCompany->correspondent_account,
                'tax_system' => $order->lessorCompany->tax_system,
                'phone' => $order->lessorCompany->phone,
                'email' => $order->lessorCompany->email,
            ],
            'lessee_company' => [
                'name' => $order->lesseeCompany->name,
                'legal_name' => $order->lesseeCompany->legal_name,
                'inn' => $order->lesseeCompany->inn,
                'kpp' => $order->lesseeCompany->kpp,
                'address' => $order->lesseeCompany->address,
                'bank_account' => $order->lesseeCompany->bank_account,
                'bank_name' => $order->lesseeCompany->bank_name,
                'bik' => $order->lesseeCompany->bik,
                'correspondent_account' => $order->lesseeCompany->correspondent_account,
                'tax_system' => $order->lesseeCompany->tax_system,
                'phone' => $order->lesseeCompany->phone,
                'email' => $order->lesseeCompany->email,
            ],
            'payment_terms' => $this->calculatePaymentTerms($scenario, $order->total_amount),
            'platform' => [
                'name' => config('app.name', 'ConstructionRental'),
                'legal_name' => config('app.company.legal_name', 'ConstructionRental Platform'),
                'inn' => config('app.company.inn', ''),
                'kpp' => config('app.company.kpp', ''),
                'address' => config('app.company.address', ''),
                'phone' => config('app.company.phone', ''),
                'email' => config('app.company.email', ''),
            ]
        ];

        // Добавляем данные УПД если есть
        if ($upd) {
            $baseData['upd'] = [
                'id' => $upd->id,
                'number' => $upd->number,
                'issue_date' => $upd->issue_date->format('d.m.Y'),
                'total_amount' => $upd->total_amount,
                'amount_without_vat' => $upd->amount,
                'vat_amount' => $upd->tax_amount,
                'service_period_start' => $upd->service_period_start?->format('d.m.Y'),
                'service_period_end' => $upd->service_period_end?->format('d.m.Y'),
            ];
        }

        // Добавляем данные по оборудованию
        if ($order->relationLoaded('equipment')) {
            $baseData['equipment'] = $order->equipment->map(function ($item) {
                $rentalPrice = $item->pivot->rental_price ?? $item->price;
                $quantity = $item->pivot->quantity ?? 1;
                $total = $rentalPrice * $quantity;

                return [
                    'name' => $item->name,
                    'model' => $item->model,
                    'serial_number' => $item->serial_number,
                    'rental_price' => $rentalPrice,
                    'quantity' => $quantity,
                    'unit' => $item->pivot->unit ?? 'шт.',
                    'total' => $total,
                    'vat_rate' => $this->getVatRateForEquipment($item),
                    'vat_amount' => $total * ($this->getVatRateForEquipment($item) / 100),
                ];
            })->toArray();

            // Суммируем общие суммы по оборудованию
            $baseData['totals'] = [
                'amount_without_vat' => collect($baseData['equipment'])->sum('total'),
                'vat_amount' => collect($baseData['equipment'])->sum('vat_amount'),
                'total_amount' => collect($baseData['equipment'])->sum('total') + collect($baseData['equipment'])->sum('vat_amount'),
            ];
        }

        // Объединяем с переданными данными
        return array_merge_recursive($baseData, $templateData);
    }

    /**
     * Создание записи счета в базе данных с использованием существующей структуры
     */
    protected function createInvoiceRecord(Order $order, string $scenario, array $invoiceData, Upd $upd = null): Invoice
    {
        return DB::transaction(function () use ($order, $scenario, $invoiceData, $upd) {
            // Создаем основной счет
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'company_id' => $order->lessee_company_id, // Плательщик - арендатор
                'upd_id' => $upd?->id,
                'number' => $invoiceData['invoice']['number'],
                'issue_date' => now(),
                'due_date' => $this->parseDueDate($invoiceData['invoice']['due_date']),
                'amount' => $this->calculateInvoiceAmount($scenario, $order, $upd),
                'amount_paid' => 0,
                'platform_fee' => $this->calculatePlatformFee($order),
                'status' => Invoice::STATUS_DRAFT, // Используем существующий статус
                'file_path' => null, // Будет обновлен после генерации файла
                'idempotency_key' => 'invoice_'.uniqid(),
            ]);

            // Создаем позиции счета если есть данные об оборудовании
            $this->createInvoiceItems($invoice, $invoiceData);

            Log::info('Запись счета создана в базе данных', [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'amount' => $invoice->amount
            ]);

            return $invoice;
        });
    }

    /**
     * Создание позиций счета
     */
    protected function createInvoiceItems(Invoice $invoice, array $invoiceData): void
    {
        if (isset($invoiceData['equipment']) && is_array($invoiceData['equipment'])) {
            foreach ($invoiceData['equipment'] as $equipmentItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $equipmentItem['name'] ?? 'Аренда оборудования',
                    'description' => $equipmentItem['model'] ?? null,
                    'quantity' => $equipmentItem['quantity'] ?? 1,
                    'unit' => $equipmentItem['unit'] ?? 'шт.',
                    'price' => $equipmentItem['rental_price'] ?? 0,
                    'amount' => $equipmentItem['total'] ?? 0,
                    'vat_rate' => $equipmentItem['vat_rate'] ?? $this->getVatRate($invoice->company),
                    'vat_amount' => $equipmentItem['vat_amount'] ?? 0,
                ]);
            }
        } else {
            // Создаем одну общую позицию если нет детализации по оборудованию
            $vatRate = $this->getVatRate($invoice->company);
            $vatAmount = $invoice->amount * ($vatRate / 100);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'name' => 'Аренда строительной техники',
                'description' => 'По заказу №' . $invoice->order->order_number,
                'quantity' => 1,
                'unit' => 'усл.',
                'price' => $invoice->amount,
                'amount' => $invoice->amount,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
            ]);
        }

        Log::info('Созданы позиции счета', [
            'invoice_id' => $invoice->id,
            'items_count' => $invoice->items()->count()
        ]);
    }

    /**
     * Расчет суммы счета в зависимости от сценария
     */
    protected function calculateInvoiceAmount(string $scenario, Order $order, Upd $upd = null): float
    {
        $amount = match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => $order->advance_amount ?? ($order->total_amount * 0.5),
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => $upd ? $upd->total_amount : $order->total_amount,
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => $order->total_amount * 0.3, // 30% частичная оплата
            DocumentTemplate::INVOICE_SCENARIO_CORRECTION => $order->total_amount, // Для коррекции может быть разная логика
            default => $order->total_amount,
        };

        // Округляем до 2 знаков после запятой
        return round($amount, 2);
    }

    /**
     * Расчет суммы аванса
     */
    protected function calculateAdvanceAmount(Order $order): float
    {
        // Логика расчета аванса: 50% от общей суммы или фиксированная сумма из заказа
        return $order->advance_amount ?? ($order->total_amount * 0.5);
    }

    /**
     * Расчет платформенного сбора
     */
    protected function calculatePlatformFee(Order $order): float
    {
        // 5% платформенный сбор от общей суммы заказа
        $fee = $order->total_amount * 0.05;
        return round($fee, 2);
    }

    /**
     * Получение ставки НДС для компании
     */
    protected function getVatRate(Company $company): float
    {
        // Логика определения ставки НДС в зависимости от налоговой системы компании
        return match($company->tax_system ?? 'osn') {
            'usn' => 0.0, // УСН без НДС
            'usn_income' => 0.0, // УСН доходы без НДС
            'patent' => 0.0, // Патент без НДС
            'envd' => 0.0, // ЕНВД без НДС
            default => 20.0, // ОСН - 20% НДС
        };
    }

    /**
     * Получение ставки НДС для оборудования
     */
    protected function getVatRateForEquipment($equipment): float
    {
        // Можно добавить логику определения ставки НДС для конкретного оборудования
        // По умолчанию используем стандартную ставку
        return 20.0;
    }

    /**
     * Генерация номера счета
     */
    protected function generateInvoiceNumber(Order $order, string $scenario): string
    {
        $prefix = match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => 'СЧ-АВ',
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_UPD => 'СЧ-АВ-УПД',
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => 'СЧ-ПП',
            DocumentTemplate::INVOICE_SCENARIO_CORRECTION => 'СЧ-КОРР',
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => 'СЧ-ЧАСТ',
            default => 'СЧ',
        };

        $datePart = date('Ymd');
        $orderPart = str_pad($order->id, 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$datePart}-{$orderPart}";
    }

    /**
     * Получение названия сценария
     */
    protected function getScenarioName(string $scenario): string
    {
        return match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => 'Предоплатный счет к заказу',
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_UPD => 'Предоплатный счет к УПД',
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => 'Постоплатный счет к УПД',
            DocumentTemplate::INVOICE_SCENARIO_CORRECTION => 'Корректировочный счет',
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => 'Счет на частичную оплату',
            default => 'Счет на оплату',
        };
    }

    /**
     * Расчет сроков оплаты
     */
    protected function calculateDueDate(string $scenario): string
    {
        $days = match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => 3, // 3 дня для предоплаты
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_UPD => 3,
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => 10, // 10 дней для постоплаты
            DocumentTemplate::INVOICE_SCENARIO_CORRECTION => 5,
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => 7,
            default => 5,
        };

        return now()->addDays($days)->format('d.m.Y');
    }

    /**
     * Парсинг даты оплаты
     */
    protected function parseDueDate(string $dueDate): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('d.m.Y', $dueDate);
    }

    /**
     * Расчет условий оплаты
     */
    protected function calculatePaymentTerms(string $scenario, float $amount): array
    {
        return match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => [
                'type' => 'предоплата',
                'percentage' => 100,
                'amount' => $amount,
                'description' => '100% предоплата до начала аренды',
                'payment_stages' => [
                    ['stage' => 'Предоплата', 'percentage' => 100, 'amount' => $amount, 'due_date' => 'до начала аренды']
                ]
            ],
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => [
                'type' => 'постоплата',
                'percentage' => 100,
                'amount' => $amount,
                'description' => '100% оплата после подписания УПД',
                'payment_stages' => [
                    ['stage' => 'Окончательный расчет', 'percentage' => 100, 'amount' => $amount, 'due_date' => 'после подписания УПД']
                ]
            ],
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => [
                'type' => 'частичная оплата',
                'percentage' => 30,
                'amount' => $amount * 0.3,
                'description' => '30% предоплата, 70% после выполнения работ',
                'payment_stages' => [
                    ['stage' => 'Аванс', 'percentage' => 30, 'amount' => $amount * 0.3, 'due_date' => 'до начала работ'],
                    ['stage' => 'Окончательный расчет', 'percentage' => 70, 'amount' => $amount * 0.7, 'due_date' => 'после выполнения работ']
                ]
            ],
            default => [
                'type' => 'стандартная оплата',
                'percentage' => 100,
                'amount' => $amount,
                'description' => 'Оплата в соответствии с условиями договора',
                'payment_stages' => [
                    ['stage' => 'Оплата', 'percentage' => 100, 'amount' => $amount, 'due_date' => 'в соответствии с договором']
                ]
            ]
        };
    }

    /**
     * Получить список доступных сценариев для заказа
     */
    public function getAvailableScenarios(Order $order): array
    {
        $scenarios = [];

        // Предоплатный счет к заказу - доступен для активных заказов
        if (in_array($order->status, ['pending', 'active'])) {
            $scenarios[DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER] = 'Предоплатный счет к заказу';
        }

        // Счет на частичную оплату - доступен для активных заказов
        if (in_array($order->status, ['pending', 'active'])) {
            $scenarios[DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT] = 'Счет на частичную оплату';
        }

        // Постоплатный счет к УПД - доступен если есть принятые УПД
        $hasAcceptedUpd = $order->upds()->where('status', 'accepted')->exists();
        if ($hasAcceptedUpd) {
            $scenarios[DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD] = 'Постоплатный счет к УПД';
        }

        return $scenarios;
    }

    /**
     * Валидация данных для генерации счета
     */
    public function validateInvoiceData(Order $order, string $scenario, Upd $upd = null): array
    {
        $errors = [];

        // Проверяем существование заказа
        if (!$order) {
            $errors[] = 'Заказ не найден';
        }

        // Проверяем статус заказа в зависимости от сценария
        if ($scenario === DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD) {
            if (!$upd) {
                $errors[] = 'Для постоплатного счета требуется УПД';
            } elseif ($upd->status !== 'accepted') {
                $errors[] = 'УПД должен быть принят для генерации постоплатного счета';
            }
        }

        // Проверяем сумму заказа
        if ($order->total_amount <= 0) {
            $errors[] = 'Сумма заказа должна быть больше нуля';
        }

        // Проверяем реквизиты компаний
        if (!$order->lessorCompany->inn) {
            $errors[] = 'У арендодателя не указан ИНН';
        }

        if (!$order->lesseeCompany->inn) {
            $errors[] = 'У арендатора не указан ИНН';
        }

        return $errors;
    }

    /**
     * Получить историю счетов по заказу
     */
    public function getOrderInvoices(Order $order)
    {
        return $order->invoices()
            ->with(['items', 'upd'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Удалить счет и связанные файлы
     */
    public function deleteInvoice(Invoice $invoice): bool
    {
        try {
            DB::transaction(function () use ($invoice) {
                // Удаляем файл счета если он существует
                if ($invoice->file_path && Storage::exists($invoice->file_path)) {
                    Storage::delete($invoice->file_path);
                }

                // Удаляем позиции счета
                $invoice->items()->delete();

                // Удаляем сам счет
                $invoice->delete();
            });

            Log::info('Счет удален', ['invoice_id' => $invoice->id]);
            return true;

        } catch (\Exception $e) {
            Log::error('Ошибка удаления счета', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Генерация упрощенного номера счета
     */
    protected function generateSimpleInvoiceNumber(Order $order, string $scenario): string
    {
        $currentYear = date('Y');
        $lastInvoice = Invoice::whereYear('created_at', $currentYear)
                            ->orderBy('id', 'desc')
                            ->first();

        $sequenceNumber = $lastInvoice ? (intval(substr($lastInvoice->number, -4)) + 1) : 1;

        $prefix = match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => 'СЧ-АВ',
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => 'СЧ-ПП',
            DocumentTemplate::INVOICE_SCENARIO_CORRECTION => 'СЧ-КОРР',
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => 'СЧ-ЧАСТ',
            default => 'СЧ',
        };

        return "{$prefix}/{$currentYear}/" . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
