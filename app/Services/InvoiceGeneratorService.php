<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Upd;
use App\Models\Company;
use App\Models\Contract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InvoiceGeneratorService
{

    /**
     * Проверка доступности генерации QR-кода
     */
    protected function isQrCodeAvailable(): bool
    {
        return class_exists('\\BaconQrCode\\Writer') &&
            class_exists('\\BaconQrCode\\Renderer\\ImageRenderer') &&
            class_exists('\\BaconQrCode\\Renderer\\RendererStyle\\RendererStyle');
    }

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

            $template = $this->findTemplateByScenario($scenario);
            if (!$template) {
                throw new \Exception("Шаблон для сценария {$scenario} не найден");
            }

            $invoiceData = $this->prepareInvoiceData($order, $scenario, $upd, $templateData);
            $invoice = $this->createInvoiceRecord($order, $scenario, $invoiceData, $upd);

            $invoice->update(['status' => Invoice::STATUS_SENT]);

            Log::info('Счет успешно создан в БД', [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number
            ]);

            return [
                'invoice' => $invoice,
                'file_path' => null
            ];

        } catch (\Exception $e) {
            Log::error('Ошибка генерации счета', [
                'order_id' => $order->id ?? 'NOT_SET',
                'scenario' => $scenario,
                'error' => $e->getMessage()
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
                'due_date' => $this->calculateDueDate($order, 'advance_order'),
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
                'due_date' => $this->calculateDueDate($order, 'postpayment_upd'),
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
                'due_date' => $this->calculateDueDate($order, 'partial_payment'),
            ]
        ]);
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
        $equipmentData = $this->getEquipmentDataForInvoice($order, $upd);

        $baseData = [
            'invoice' => [
                'number' => $this->generateSimpleInvoiceNumber($order, $scenario),
                'date' => now()->format('d.m.Y'),
                'scenario' => $scenario,
                'scenario_name' => $this->getScenarioName($scenario),
                'due_date' => $this->calculateDueDate($order, $scenario),
                'is_advance' => $scenario === DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER,
                'is_postpayment' => $scenario === DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD,
                'is_partial' => $scenario === DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT,
            ],
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'date' => $this->safeDateFormat($order->created_at),
                'start_date' => $this->safeDateFormat($order->start_date),
                'end_date' => $this->safeDateFormat($order->end_date),
                'total_amount' => $order->total_amount,
                'advance_amount' => $order->advance_amount ?? 0,
                'remaining_amount' => $order->total_amount - ($order->advance_amount ?? 0),
                'contract_number' => $order->contract_number,
                'contract_date' => $this->safeDateFormat($order->contract_date),
                'delivery_address' => $order->delivery_address ?? '',
                'installation_address' => $order->installation_address ?? '',
                'rental_period_days' => $this->calculateRentalPeriodDays($order),
            ],
            'lessee_company' => $this->prepareCompanyData($order->lesseeCompany),
            'platform' => $this->preparePlatformData(),
            'rental_period' => [
                'start_date' => $this->safeDateFormat($order->start_date),
                'end_date' => $this->safeDateFormat($order->end_date),
                'total_days' => $this->calculateRentalPeriodDays($order),
                'description' => $this->generateRentalPeriodDescription($order),
            ],
            'equipment_general' => $equipmentData,
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
                'service_period_days' => $upd->service_period_start && $upd->service_period_end
                    ? $upd->service_period_start->diffInDays($upd->service_period_end)
                    : 0,
            ];
        }

        // Добавляем текстовое представление суммы
        $totalAmount = $upd ? $upd->total_amount : $order->total_amount;
        $baseData['amount_in_words'] = $this->amountToWords($totalAmount);

        return array_merge_recursive($baseData, $templateData);
    }

    /**
     * Подготовка данных компании
     */
    protected function prepareCompanyData(Company $company): array
    {
        return [
            'name' => $company->name,
            'legal_name' => $company->legal_name,
            'inn' => $company->inn,
            'kpp' => $company->kpp,
            'address' => $company->address,
            'legal_address' => $company->legal_address ?? $company->address,
            'actual_address' => $company->actual_address ?? $company->address,
            'bank_account' => $company->bank_account,
            'bank_name' => $company->bank_name,
            'bik' => $company->bik,
            'correspondent_account' => $company->correspondent_account,
            'tax_system' => $company->tax_system,
            'phone' => $company->phone,
            'email' => $company->email,
            'general_director' => $company->general_director ?? '',
            'acting_basis' => $company->acting_basis ?? 'Устава',
        ];
    }

    /**
     * Подготовка данных платформы
     */
    protected function preparePlatformData(): array
    {
        return [
            'name' => config('app.name', 'ConstructionRental'),
            'legal_name' => config('app.company.legal_name', 'ConstructionRental Platform'),
            'inn' => config('app.company.inn', ''),
            'kpp' => config('app.company.kpp', ''),
            'address' => config('app.company.address', ''),
            'phone' => config('app.company.phone', ''),
            'email' => config('app.company.email', ''),
        ];
    }

    /**
     * Получает данные об оборудовании для счета
     */
    protected function getEquipmentDataForInvoice(Order $order, ?Upd $upd = null): array
    {
        $equipmentData = [
            'name' => '',
            'model' => '',
            'vehicle_number' => '',
            'full_description' => ''
        ];

        try {
            // ПЕРВЫЙ ПРИОРИТЕТ: данные из УПД и путевого листа
            if ($upd && $upd->waybill) {
                $waybill = $upd->waybill;

                if (!empty($waybill->license_plate)) {
                    $equipmentData['vehicle_number'] = $waybill->license_plate;
                }

                if ($waybill->equipment) {
                    $equipment = $waybill->equipment;
                    if (empty($equipmentData['vehicle_number']) && !empty($equipment->license_plate)) {
                        $equipmentData['vehicle_number'] = $equipment->license_plate;
                    }
                    if (!empty($equipment->title)) $equipmentData['name'] = $equipment->title;
                    if (!empty($equipment->model)) $equipmentData['model'] = $equipment->model;
                }
            }

            // ВТОРОЙ ПРИОРИТЕТ: оборудование из позиций заказа - ИСПРАВЛЕНО
            if ((empty($equipmentData['vehicle_number']) || empty($equipmentData['name'])) && $order->relationLoaded('items')) {
                foreach ($order->items as $item) {
                    // Проверяем, есть ли отношение equipment у позиции заказа
                    if ($item->relationLoaded('equipment') && $item->equipment) {
                        $equipment = $item->equipment;
                        if (empty($equipmentData['vehicle_number']) && !empty($equipment->license_plate)) {
                            $equipmentData['vehicle_number'] = $equipment->license_plate;
                        }
                        if (empty($equipmentData['name']) && !empty($equipment->name)) {
                            $equipmentData['name'] = $equipment->name;
                        }
                        if (empty($equipmentData['model']) && !empty($equipment->model)) {
                            $equipmentData['model'] = $equipment->model;
                        }

                        // Если нашли все данные, выходим из цикла
                        if (!empty($equipmentData['vehicle_number']) && !empty($equipmentData['name'])) {
                            break;
                        }
                    }
                }
            }

            // ТРЕТИЙ ПРИОРИТЕТ: данные из самого заказа
            if (empty($equipmentData['vehicle_number']) && !empty($order->vehicle_number)) {
                $equipmentData['vehicle_number'] = $order->vehicle_number;
            }

            // Формируем полное описание
            $parts = [];
            if (!empty($equipmentData['name'])) $parts[] = $equipmentData['name'];
            if (!empty($equipmentData['model'])) $parts[] = $equipmentData['model'];
            if (!empty($equipmentData['vehicle_number'])) $parts[] = 'г.р.з. ' . $equipmentData['vehicle_number'];
            $equipmentData['full_description'] = implode(' ', $parts);

        } catch (\Exception $e) {
            Log::error('Ошибка получения данных об оборудовании для счета', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }

        return $equipmentData;
    }

    /**
     * Создание записи счета
     */
    protected function createInvoiceRecord(Order $order, string $scenario, array $invoiceData, Upd $upd = null): Invoice
    {
        return DB::transaction(function () use ($order, $scenario, $invoiceData, $upd) {
            $dueDate = $this->parseDueDate($invoiceData['invoice']['due_date']);

            // Загружаем необходимые отношения - ИСПРАВЛЕНО
            if (!$order->relationLoaded('items')) {
                $order->load(['items.equipment']); // Загружаем позиции с оборудованием
            }

            if ($upd && !$upd->relationLoaded('waybill')) {
                $upd->load(['waybill.equipment']);
            }

            $invoice = Invoice::create([
                'order_id' => $order->id,
                'company_id' => $order->lessee_company_id,
                'upd_id' => $upd?->id,
                'number' => $invoiceData['invoice']['number'],
                'issue_date' => now(),
                'due_date' => $dueDate,
                'amount' => $this->calculateInvoiceAmount($scenario, $order, $upd),
                'amount_paid' => 0,
                'platform_fee' => $this->calculatePlatformFee($order),
                'status' => Invoice::STATUS_DRAFT,
                'file_path' => null,
                'idempotency_key' => 'invoice_'.uniqid(),
            ]);

            // Создаем позиции счета
            $this->createInvoiceItems($invoice, $invoiceData);

            return $invoice;
        });
    }

    /**
     * Создание позиций счета
     */
    protected function createInvoiceItems(Invoice $invoice, array $invoiceData): void
    {
        // ПРИОРИТЕТ: Позиции на основе данных УПД
        if ($invoice->upd) {
            $this->createInvoiceItemsFromUpd($invoice, $invoiceData);
            return;
        }

        // Общая позиция на основе заказа
        $this->createGeneralInvoiceItem($invoice, $invoiceData);
    }

    /**
     * Создание позиций на основе данных УПД
     */
    protected function createInvoiceItemsFromUpd(Invoice $invoice, array $invoiceData): void
    {
        try {
            $upd = $invoice->upd;
            $upd->load('items');

            $vehicleNumber = $invoiceData['equipment_general']['vehicle_number'] ?? null;

            if ($upd->items->count() > 0) {
                foreach ($upd->items as $updItem) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'name' => $updItem->name ?: 'Аренда строительной техники',
                        'description' => $updItem->description ?? $this->generateUpdItemDescription($updItem, $invoiceData, $vehicleNumber),
                        'quantity' => $updItem->quantity ?? 1,
                        'unit' => $updItem->unit ?: 'усл.',
                        'price' => $updItem->price ?? 0,
                        'amount' => $updItem->amount ?? 0,
                        'vat_rate' => $updItem->vat_rate ?? $this->getVatRate($invoice->company),
                        'vat_amount' => $updItem->vat_amount ?? 0,
                    ]);
                }
            } else {
                $this->createGeneralInvoiceItem($invoice, $invoiceData);
            }

        } catch (\Exception $e) {
            Log::error('Ошибка создания позиций из УПД', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            $this->createGeneralInvoiceItem($invoice, $invoiceData);
        }
    }

    /**
     * Создание общей позиции счета
     */
    protected function createGeneralInvoiceItem(Invoice $invoice, array $invoiceData): void
    {
        $vatRate = $this->getVatRate($invoice->company);
        $vatAmount = $invoice->amount * ($vatRate / 100);

        $description = $this->generateGeneralDescription($invoiceData);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Аренда строительной техники',
            'description' => $description,
            'quantity' => 1,
            'unit' => 'усл.',
            'price' => $invoice->amount,
            'amount' => $invoice->amount,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
        ]);
    }

    /**
     * Подготовка данных счета для скачивания - С QR-КОДОМ И РЕКВИЗИТАМИ
     */
    public function prepareInvoiceDataForDownload(Invoice $invoice): array
    {
        $order = $invoice->order;
        $upd = $invoice->upd;

        // Определяем сценарий
        $scenario = $invoice->upd_id
            ? DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD
            : DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER;

        // Получаем базовые данные
        $invoiceData = $this->prepareInvoiceData($order, $scenario, $upd);
        $platformCompany = Company::where('is_platform', true)->first();
        $lesseeCompany = $order->lesseeCompany;
        $contractData = $this->getContractDataForInvoice($order);

        // Генерируем QR-код
        $paymentQrCode = $this->generatePaymentQrCode($invoice);
        $bankDetails = $this->prepareBankDetails($invoice);

        // Получаем полные данные об оборудовании
        $equipmentData = $this->getFullEquipmentData($order, $upd);

        // Подготавливаем позиции счета
        $invoiceItems = $this->prepareInvoiceItemsWithVat($invoice, [
            'equipment' => $equipmentData,
            'upd' => $upd ? [
                'number' => $upd->number,
                'date' => $upd->issue_date->format('d.m.Y'),
                'service_period_start' => $upd->service_period_start?->format('d.m.Y'),
                'service_period_end' => $upd->service_period_end?->format('d.m.Y'),
            ] : null
        ]);

        // СОЗДАЕМ ДАННЫЕ В ПРАВИЛЬНОМ ФОРМАТЕ ДЛЯ AUTOMAPPING
        $templateData = [
            'invoice_number' => $invoice->number,
            'invoice_date' => $invoice->issue_date->format('d.m.Y'),
            'invoice_total_amount' => $invoice->amount,
            'invoice_due_date' => $invoice->due_date->format('d.m.Y'),
            'amount_in_words' => $this->amountToWords($invoice->amount),

            // Основные данные счета
            'invoice' => [
                'number' => $invoice->number,
                'date' => $invoice->issue_date->format('d.m.Y'),
                'due_date' => $invoice->due_date->format('d.m.Y'),
                'total_amount' => $invoice->amount,
                'scenario' => $scenario,
                'scenario_name' => $this->getScenarioName($scenario),
            ],

            // Поставщик (платформа)
            'supplier' => [
                'name' => $platformCompany->legal_name,
                'legal_name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
                'inn_kpp' => $platformCompany->inn . ' / ' . $platformCompany->kpp,
                'bank_name' => $platformCompany->bank_name,
                'bik' => $platformCompany->bik,
                'account_number' => $platformCompany->bank_account,
                'correspondent_account' => $platformCompany->correspondent_account,
            ],

            // Плательщик (арендатор)
            'payer' => [
                'name' => $lesseeCompany->legal_name,
                'legal_name' => $lesseeCompany->legal_name,
                'address' => $lesseeCompany->legal_address,
                'inn' => $lesseeCompany->inn,
                'kpp' => $lesseeCompany->kpp,
                'inn_kpp' => $lesseeCompany->inn . ' / ' . $lesseeCompany->kpp,
            ],

            // Для обратной совместимости
            'buyer' => [
                'name' => $lesseeCompany->legal_name,
                'legal_name' => $lesseeCompany->legal_name,
                'address' => $lesseeCompany->legal_address,
                'inn' => $lesseeCompany->inn,
                'kpp' => $lesseeCompany->kpp,
                'inn_kpp' => $lesseeCompany->inn . ' / ' . $lesseeCompany->kpp,
            ],

            // Данные заказа и договора
            'order' => [
                'number' => $order->order_number,
                'date' => $order->created_at->format('d.m.Y'),
                'contract_number' => $contractData['contract_number'],
                'contract_date' => $contractData['contract_date'],
                'start_date' => $order->start_date ? $order->start_date->format('d.m.Y') : '',
                'end_date' => $order->end_date ? $order->end_date->format('d.m.Y') : '',
                'total_amount' => $order->total_amount,
            ],

            // Данные УПД если есть
            'upd' => $upd ? [
                'number' => $upd->number,
                'date' => $upd->issue_date->format('d.m.Y'),
                'total_amount' => $upd->total_amount,
                'service_period_start' => $upd->service_period_start?->format('d.m.Y'),
                'service_period_end' => $upd->service_period_end?->format('d.m.Y'),
            ] : null,

            // Данные об оборудовании
            'equipment' => $equipmentData,
            'equipment_general' => $equipmentData,

            // Позиции счета в ПРАВИЛЬНОМ ФОРМАТЕ
            'items' => $invoiceItems,

            // QR-код и реквизиты
            'payment_qr_code' => $paymentQrCode,
            'bank_details' => $bankDetails,
            'has_qr_code' => !empty($paymentQrCode),
        ];

        // ДОБАВЛЯЕМ ДАННЫЕ ДЛЯ AUTOMAPPING В ПЛОСКОМ ФОРМАТЕ
        $this->addAutomappingData($templateData, $invoiceItems);

        $result = array_merge($invoiceData, $templateData);

        Log::info('Данные для скачивания счета подготовлены', [
            'invoice_id' => $invoice->id,
            'items_count' => count($invoiceItems),
            'has_automapping_data' => isset($result['invoice_items']),
            'vehicle_number' => $equipmentData['vehicle_number'] ?? 'NOT_FOUND'
        ]);

        return $result;
    }

    /**
     * Добавляет данные для automapping в плоском формате
     */
   protected function addAutomappingData(array &$templateData, array $invoiceItems): void
    {
        // Добавляем данные для автоматической подстановки в ДВУХ ФОРМАТАХ
        foreach ($invoiceItems as $index => $item) {
            // ФОРМАТ 1: invoice_items.0.field (для нового automapping)
            $templateData["invoice_items.{$index}.name"] = $item['name'];
            $templateData["invoice_items.{$index}.quantity"] = $item['quantity'];
            $templateData["invoice_items.{$index}.unit"] = $item['unit'];
            $templateData["invoice_items.{$index}.price"] = $item['price'];
            $templateData["invoice_items.{$index}.amount"] = $item['amount'];
            $templateData["invoice_items.{$index}.vat_rate"] = $item['vat_rate'];
            $templateData["invoice_items.{$index}.vat_amount"] = $item['vat_amount'];
            $templateData["invoice_items.{$index}.total_with_vat"] = $item['total_with_vat'];
            $templateData["invoice_items.{$index}.total_without_vat"] = $item['total_without_vat'];

            // ФОРМАТ 2: invoice_items[0].field (для обратной совместимости) - ДОБАВЬТЕ ЭТО!
            $templateData["invoice_items[{$index}].name"] = $item['name'];
            $templateData["invoice_items[{$index}].quantity"] = $item['quantity'];
            $templateData["invoice_items[{$index}].unit"] = $item['unit'];
            $templateData["invoice_items[{$index}].price"] = $item['price'];
            $templateData["invoice_items[{$index}].amount"] = $item['amount'];
            $templateData["invoice_items[{$index}].vat_rate"] = $item['vat_rate'];
            $templateData["invoice_items[{$index}].vat_amount"] = $item['vat_amount'];
            $templateData["invoice_items[{$index}].total_with_vat"] = $item['total_with_vat'];
            $templateData["invoice_items[{$index}].total_without_vat"] = $item['total_without_vat'];
        }

        // Также добавляем общие данные
        $templateData['invoice_total_amount'] = $templateData['invoice']['total_amount'];
        $templateData['invoice_number'] = $templateData['invoice']['number'];
        $templateData['invoice_date'] = $templateData['invoice']['date'];
        $templateData['invoice_due_date'] = $templateData['invoice']['due_date'];

        // Данные поставщика
        $templateData['supplier_name'] = $templateData['supplier']['name'];
        $templateData['supplier_inn'] = $templateData['supplier']['inn'];
        $templateData['supplier_kpp'] = $templateData['supplier']['kpp'];
        $templateData['supplier_address'] = $templateData['supplier']['address'];
        $templateData['supplier_bank_name'] = $templateData['supplier']['bank_name'];
        $templateData['supplier_bik'] = $templateData['supplier']['bik'];
        $templateData['supplier_account_number'] = $templateData['supplier']['account_number'];

        // Данные плательщика
        $templateData['payer_name'] = $templateData['payer']['name'];
        $templateData['payer_inn'] = $templateData['payer']['inn'];
        $templateData['payer_kpp'] = $templateData['payer']['kpp'];
        $templateData['payer_address'] = $templateData['payer']['address'];
    }
    /**
     * Получает полные данные об оборудовании для счета
     */
    public function getFullEquipmentData(Order $order, ?Upd $upd = null): array
    {
        $equipmentData = [
            'name' => '',
            'model' => '',
            'vehicle_number' => '',
            'full_description' => ''
        ];

        try {
            Log::debug('=== АГРЕССИВНЫЙ ПОИСК ДАННЫХ ОБОРУДОВАНИЯ ===', [
                'order_id' => $order->id,
                'upd_id' => $upd?->id,
                'has_upd' => !is_null($upd)
            ]);

            // ПРИОРИТЕТ 1: Данные из УПД и путевого листа
            if ($upd) {
                Log::debug('Поиск в УПД', ['upd_id' => $upd->id]);

                if ($upd->relationLoaded('waybill') && $upd->waybill) {
                    $waybill = $upd->waybill;
                    Log::debug('Путевой лист найден', [
                        'waybill_id' => $waybill->id,
                        'license_plate' => $waybill->license_plate
                    ]);

                    if (!empty($waybill->license_plate)) {
                        $equipmentData['vehicle_number'] = $waybill->license_plate;
                        Log::debug('Гос. номер из путевого листа', [
                            'vehicle_number' => $waybill->license_plate
                        ]);
                    }

                    if ($waybill->relationLoaded('equipment') && $waybill->equipment) {
                        $equipment = $waybill->equipment;
                        if (empty($equipmentData['vehicle_number']) && !empty($equipment->license_plate)) {
                            $equipmentData['vehicle_number'] = $equipment->license_plate;
                            Log::debug('Гос. номер из оборудования путевого листа', [
                                'vehicle_number' => $equipment->license_plate
                            ]);
                        }
                        if (!empty($equipment->title)) $equipmentData['name'] = $equipment->title;
                        if (!empty($equipment->model)) $equipmentData['model'] = $equipment->model;
                    }
                }

                if (empty($equipmentData['vehicle_number']) && !empty($upd->vehicle_number)) {
                    $equipmentData['vehicle_number'] = $upd->vehicle_number;
                    Log::debug('Гос. номер из самого УПД', [
                        'vehicle_number' => $upd->vehicle_number
                    ]);
                }
            }

            // ПРИОРИТЕТ 2: Оборудование из позиций заказа
            if ((empty($equipmentData['vehicle_number']) || empty($equipmentData['name'])) && $order->relationLoaded('items')) {
                Log::debug('Поиск в позициях заказа', [
                    'items_count' => $order->items->count()
                ]);

                foreach ($order->items as $item) {
                    if ($item->relationLoaded('equipment') && $item->equipment) {
                        $equipment = $item->equipment;
                        if (empty($equipmentData['vehicle_number']) && !empty($equipment->license_plate)) {
                            $equipmentData['vehicle_number'] = $equipment->license_plate;
                            Log::debug('Гос. номер из оборудования позиции заказа', [
                                'item_id' => $item->id,
                                'vehicle_number' => $equipment->license_plate
                            ]);
                        }
                        if (empty($equipmentData['name']) && !empty($equipment->name)) {
                            $equipmentData['name'] = $equipment->name;
                        }
                        if (empty($equipmentData['model']) && !empty($equipment->model)) {
                            $equipmentData['model'] = $equipment->model;
                        }

                        if (!empty($equipmentData['vehicle_number']) && !empty($equipmentData['name'])) {
                            break;
                        }
                    }
                }
            }

            // ПРИОРИТЕТ 3: Данные из самого заказа
            if (empty($equipmentData['vehicle_number']) && !empty($order->vehicle_number)) {
                $equipmentData['vehicle_number'] = $order->vehicle_number;
                Log::debug('Гос. номер из заказа', [
                    'vehicle_number' => $order->vehicle_number
                ]);
            }

            // Формируем полное описание
            $parts = [];
            if (!empty($equipmentData['name'])) $parts[] = $equipmentData['name'];
            if (!empty($equipmentData['model'])) $parts[] = $equipmentData['model'];
            if (!empty($equipmentData['vehicle_number'])) $parts[] = 'г.р.з. ' . $equipmentData['vehicle_number'];
            $equipmentData['full_description'] = implode(' ', $parts);

            Log::debug('=== ИТОГОВЫЕ ДАННЫЕ ОБОРУДОВАНИЯ ===', $equipmentData);

        } catch (\Exception $e) {
            Log::error('Ошибка получения данных об оборудовании', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }

        return $equipmentData;
    }

    /**
     * Подготовка позиций счета с ценами С НДС - ПРОСТОЙ И ТОЧНЫЙ ВАРИАНТ
     */
    protected function prepareInvoiceItemsWithVat(Invoice $invoice, array $templateData): array
    {
        return $invoice->items->map(function ($item, $index) use ($invoice, $templateData) {
            // Расчет сумм
            $quantity = $item->quantity;
            $priceWithoutVat = $item->price;

            // Если счет привязан к УПД, используем точные суммы из УПД
            if ($invoice->upd && $invoice->upd->items->count() > 0) {
                $updItem = $invoice->upd->items[$index] ?? $invoice->upd->items->first();
                $amountWithoutVat = $updItem->amount ?? ($quantity * $priceWithoutVat);
                $vatAmount = $updItem->vat_amount ?? ($amountWithoutVat * ($item->vat_rate / 100));
                $amountWithVat = $updItem->amount + $updItem->vat_amount ?? ($amountWithoutVat + $vatAmount);
                $priceWithVat = $amountWithVat / $quantity;
            } else {
                // Расчет для счетов без УПД
                $amountWithoutVat = $quantity * $priceWithoutVat;
                $vatAmount = $amountWithoutVat * ($item->vat_rate / 100);
                $amountWithVat = $amountWithoutVat + $vatAmount;
                $priceWithVat = $amountWithVat / $quantity;
            }

            // Округление до 2 знаков
            $priceWithVat = round($priceWithVat, 2);
            $amountWithVat = round($amountWithVat, 2);
            $vatAmount = round($vatAmount, 2);
            $amountWithoutVat = round($amountWithoutVat, 2);

            // ГАРАНТИРОВАННАЯ ПОДСТАНОВКА ГОС. НОМЕРА
            $vehicleNumber = $templateData['equipment']['vehicle_number'] ?? '';
            $baseName = $item->name;

            $fullItemName = $this->ensureVehicleNumberInName($baseName, $vehicleNumber);

            Log::debug('Подготовка позиции для Excel', [
                'item_id' => $item->id,
                'original_name' => $baseName,
                'final_name' => $fullItemName,
                'vehicle_number' => $vehicleNumber,
                'quantity' => $quantity,
                'price' => $priceWithVat,
                'amount' => $amountWithVat
            ]);

            return [
                'name' => $fullItemName,
                'name_short' => $item->name,
                'description' => $item->description,
                'quantity' => $quantity,
                'unit' => $item->unit,
                'price' => $priceWithVat,
                'amount' => $amountWithVat,
                'vat_rate' => $item->vat_rate,
                'vat_amount' => $vatAmount,
                'total_with_vat' => $amountWithVat,
                'total_without_vat' => $amountWithoutVat,
                'total' => $amountWithVat,
                'index' => $index + 1,
                'equipment_name' => $templateData['equipment']['name'] ?? '',
                'equipment_model' => $templateData['equipment']['model'] ?? '',
                'vehicle_number' => $vehicleNumber,
            ];
        })->toArray();
    }

    protected function ensureVehicleNumberInName(string $itemName, string $vehicleNumber): string
    {
        if (empty($vehicleNumber)) {
            return $itemName;
        }

        // Если гос. номер уже есть в правильном формате, возвращаем как есть
        if (strpos($itemName, "(гос. номер: {$vehicleNumber})") !== false) {
            return $itemName;
        }

        // Заменяем пустой плейсхолдер
        if (strpos($itemName, '(гос. номер: )') !== false) {
            return str_replace('(гос. номер: )', "(гос. номер: {$vehicleNumber})", $itemName);
        }

        // Добавляем гос. номер если его нет
        if (strpos($itemName, 'гос. номер') === false) {
            if (strpos($itemName, 'за период') !== false) {
                return str_replace('за период', "(гос. номер: {$vehicleNumber}) за период", $itemName);
            } else {
                return $itemName . " (гос. номер: {$vehicleNumber})";
            }
        }

        // Заменяем любой существующий гос. номер
        if (preg_match('/\(гос\. номер:\s*[^)]*\)/', $itemName)) {
            return preg_replace('/\(гос\. номер:\s*[^)]*\)/', "(гос. номер: {$vehicleNumber})", $itemName);
        }

        return $itemName;
    }

    /**
     * Генерация полного названия позиции с учетом данных об оборудовании
     */
    protected function generateInvoiceItemNameWithEquipment($item, Order $order, array $templateData, int $index): string
    {
        $baseName = $item->name;
        $vehicleNumber = $templateData['equipment']['vehicle_number'] ?? '';
        $equipmentName = $templateData['equipment']['name'] ?? '';
        $equipmentModel = $templateData['equipment']['model'] ?? '';

        // Если название стандартное, формируем полное описание
        if (in_array(mb_strtolower(trim($baseName)), ['аренда', 'услуги', 'аренда оборудования', 'оказание услуг'])) {
            $parts = ['Аренда'];

            // Добавляем название оборудования если есть
            if (!empty($equipmentName)) {
                $parts[] = $equipmentName;
            }

            if (!empty($equipmentModel)) {
                $parts[] = $equipmentModel;
            }

            // Добавляем период
            if (!empty($order->start_date) && !empty($order->end_date)) {
                $startDate = $order->start_date->format('d.m.Y');
                $endDate = $order->end_date->format('d.m.Y');
                $parts[] = "за период {$startDate} - {$endDate}";
            }

            // Добавляем гос. номер если есть
            if (!empty($vehicleNumber)) {
                $parts[] = "(гос. номер: {$vehicleNumber})";
            }

            $fullName = implode(' ', $parts);
            Log::debug('Сгенерировано полное название позиции', [
                'base_name' => $baseName,
                'full_name' => $fullName,
                'vehicle_number' => $vehicleNumber
            ]);

            return $fullName;
        }

        // Если в названии есть плейсхолдеры, заменяем их
        if (strpos($baseName, '{{vehicle_number}}') !== false) {
            if (!empty($vehicleNumber)) {
                $baseName = str_replace('{{vehicle_number}}', $vehicleNumber, $baseName);
            } else {
                $baseName = str_replace('{{vehicle_number}}', '', $baseName);
            }
        }

        if (strpos($baseName, '{{period}}') !== false && !empty($order->start_date) && !empty($order->end_date)) {
            $period = $order->start_date->format('d.m.Y') . ' - ' . $order->end_date->format('d.m.Y');
            $baseName = str_replace('{{period}}', $period, $baseName);
        }

        // Убираем возможные двойные пробелы
        $baseName = preg_replace('/\s+/', ' ', $baseName);
        $baseName = trim($baseName);

        return $baseName;
    }

    /**
     * Генерация универсального QR-кода с реквизитами для оплаты
     */
    protected function generatePaymentQrCode(Invoice $invoice): string
    {
        try {
            Log::debug('=== QR-КОД ТОЛЬКО ЛАТИНИЦА И ЦИФРЫ ===', ['invoice_id' => $invoice->id]);

            $platformCompany = Company::where('is_platform', true)->first();

            if (!$platformCompany) {
                return '';
            }

            // ТОЛЬКО латинские буквы A-Z, a-z, цифры 0-9 и основные символы
            $qrContent = "INV" . preg_replace('/[^A-Za-z0-9]/', '', $invoice->number) . "|";
            $qrContent .= "AMT" . $invoice->amount . "|";
            $qrContent .= "ACC" . $platformCompany->bank_account . "|";
            $qrContent .= "BIC" . $platformCompany->bik . "|";
            $qrContent .= "INN" . $platformCompany->inn;

            Log::debug('Чистое латинское содержимое', [
                'content' => $qrContent,
                'content_length' => strlen($qrContent)
            ]);

            return $this->generateQrCodeWithBacon($qrContent);

        } catch (\Exception $e) {
            Log::error('Ошибка генерации чистого латинского QR-кода', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Транслитерация русских символов в латиницу
     */
    protected function transliterateToLatin(string $text): string
    {
        $cyrillic = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
            'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
        ];

        $latin = [
            'a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
            'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya',
            'A','B','V','G','D','E','E','Zh','Z','I','Y','K','L','M','N','O','P',
            'R','S','T','U','F','H','Ts','Ch','Sh','Sch','','Y','','E','Yu','Ya'
        ];

        return str_replace($cyrillic, $latin, $text);
    }

    /**
     * Генерация QR-кода с использованием bacon/bacon-qr-code
     */
    protected function generateQrCodeWithBacon(string $content): string
    {
        try {
            Log::debug('Начало генерации QR-кода с Bacon', ['content_length' => strlen($content)]);

            // Проверяем доступные расширения
            $availableExtensions = [
                'imagick' => extension_loaded('imagick'),
                'gd' => extension_loaded('gd')
            ];

            Log::debug('Доступные расширения', $availableExtensions);

            // Выбираем бэкенд
            if ($availableExtensions['imagick']) {
                Log::debug('Используем Imagick бэкенд');
                $backend = new \BaconQrCode\Renderer\Image\ImagickImageBackEnd();
            } elseif ($availableExtensions['gd']) {
                Log::debug('Используем GD бэкенд');
                $backend = new \BaconQrCode\Renderer\Image\GdImageBackEnd();
            } else {
                Log::warning('Нет доступных бэкендов для генерации изображений');
                return '';
            }

            // Создаем стиль рендерера
            $rendererStyle = new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300);

            // Создаем рендерер
            $renderer = new \BaconQrCode\Renderer\ImageRenderer($rendererStyle, $backend);

            // Создаем писатель
            $writer = new \BaconQrCode\Writer($renderer);

            // Генерируем QR-код
            $qrCode = $writer->writeString($content);

            Log::debug('QR-код успешно сгенерирован', [
                'output_size' => strlen($qrCode),
                'backend_used' => $availableExtensions['imagick'] ? 'imagick' : 'gd'
            ]);

            return $qrCode;

        } catch (\Exception $e) {
            Log::error('Ошибка генерации QR-кода через Bacon', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '';
        }
    }

    /**
     * Генерация назначения платежа для QR-кода
     */
    protected function generatePaymentPurpose(Invoice $invoice): string
    {
        $purpose = "Оплата счета №" . $invoice->number;

        if ($invoice->upd) {
            $purpose .= " по УПД №" . $invoice->upd->number;
        }

        $purpose .= " от " . $invoice->issue_date->format('d.m.Y');

        // Ограничиваем длину для QR-кода
        return mb_substr($purpose, 0, 140);
    }


    /**
     * Подготовка банковских реквизитов для отображения в документе
     */
    protected function prepareBankDetails(Invoice $invoice): array
    {
        $platformCompany = Company::where('is_platform', true)->first();

        if (!$platformCompany) {
            return [
                'error' => 'Компания платформы не найдена'
            ];
        }

        return [
            'recipient' => $platformCompany->legal_name,
            'inn' => $platformCompany->inn,
            'kpp' => $platformCompany->kpp,
            'bank_name' => $platformCompany->bank_name,
            'bik' => $platformCompany->bik,
            'account_number' => $platformCompany->bank_account,
            'correspondent_account' => $platformCompany->correspondent_account,
            'purpose' => $this->generatePaymentPurpose($invoice),
            'amount' => number_format($invoice->amount, 2, ',', ' '),
            'invoice_number' => $invoice->number,
            'invoice_date' => $invoice->issue_date->format('d.m.Y'),
            'due_date' => $invoice->due_date->format('d.m.Y'),
        ];
    }

    /**
     * Генерация полного названия для позиции
     */
    protected function generateInvoiceItemName($item, Order $order, array $templateData, int $index): string
    {
        $baseName = $item->name;

        if (in_array(mb_strtolower(trim($baseName)), ['аренда', 'услуги', 'аренда оборудования', 'оказание услуг'])) {
            $parts = ['Аренда'];

            if (!empty($templateData['equipment']['full_description'])) {
                $parts[] = $templateData['equipment']['full_description'];
            } else {
                $parts[] = 'строительной техники';
            }

            if (!empty($order->start_date) && !empty($order->end_date)) {
                $startDate = $order->start_date->format('d.m.Y');
                $endDate = $order->end_date->format('d.m.Y');
                $parts[] = "за период {$startDate} - {$endDate}";
            }

            if (!empty($templateData['equipment']['vehicle_number'])) {
                $parts[] = "(гос. номер: {$templateData['equipment']['vehicle_number']})";
            }

            return implode(' ', $parts);
        }

        // Если в названии есть плейсхолдеры, заменяем их
        if (strpos($baseName, '{{vehicle_number}}') !== false && !empty($templateData['equipment']['vehicle_number'])) {
            $baseName = str_replace('{{vehicle_number}}', $templateData['equipment']['vehicle_number'], $baseName);
        }

        if (strpos($baseName, '{{period}}') !== false && !empty($order->start_date) && !empty($order->end_date)) {
            $period = $order->start_date->format('d.m.Y') . ' - ' . $order->end_date->format('d.m.Y');
            $baseName = str_replace('{{period}}', $period, $baseName);
        }

        return $baseName;
    }

    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ

    /**
     * Генерация описания для позиции УПД
     */
    protected function generateUpdItemDescription($updItem, array $invoiceData, ?string $vehicleNumber = null): string
    {
        $descriptions = [];

        if (!empty($updItem->description)) {
            $descriptions[] = $updItem->description;
        }

        if (isset($invoiceData['upd']['number'])) {
            $descriptions[] = "УПД №{$invoiceData['upd']['number']}";
        }

        if (!empty($vehicleNumber)) {
            $descriptions[] = "Гос. номер: {$vehicleNumber}";
        }

        if (isset($invoiceData['upd']['service_period_start']) && isset($invoiceData['upd']['service_period_end'])) {
            $startDate = $invoiceData['upd']['service_period_start'];
            $endDate = $invoiceData['upd']['service_period_end'];
            $descriptions[] = "Период услуг: {$startDate} - {$endDate}";
        }

        return implode(', ', $descriptions) ?: 'Услуги по аренде техники';
    }

    /**
     * Генерация общего описания
     */
    protected function generateGeneralDescription(array $invoiceData): string
    {
        $descriptions = [];

        if (isset($invoiceData['order']['number'])) {
            $descriptions[] = "По заказу №{$invoiceData['order']['number']}";
        }

        if (isset($invoiceData['order']['start_date']) && isset($invoiceData['order']['end_date'])) {
            $startDate = $invoiceData['order']['start_date'];
            $endDate = $invoiceData['order']['end_date'];
            $descriptions[] = "Период аренды: {$startDate} - {$endDate}";
        }

        if (isset($invoiceData['upd']['number'])) {
            $descriptions[] = "По УПД №{$invoiceData['upd']['number']}";
        }

        return implode(', ', $descriptions) ?: 'Аренда строительной техники';
    }

    /**
     * Расчет суммы счета
     */
    protected function calculateInvoiceAmount(string $scenario, Order $order, Upd $upd = null): float
    {
        $amount = match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => $order->advance_amount ?? ($order->total_amount * 0.5),
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => $upd ? $upd->total_amount : $order->total_amount,
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => $order->total_amount * 0.3,
            default => $order->total_amount,
        };

        return round($amount, 2);
    }

    /**
     * Расчет суммы аванса
     */
    protected function calculateAdvanceAmount(Order $order): float
    {
        return $order->advance_amount ?? ($order->total_amount * 0.5);
    }

    /**
     * Расчет платформенного сбора
     */
    protected function calculatePlatformFee(Order $order): float
    {
        return round($order->total_amount * 0.05, 2);
    }

    /**
     * Получение ставки НДС
     */
    protected function getVatRate(Company $company): float
    {
        return match($company->tax_system ?? 'osn') {
            'usn', 'usn_income', 'patent', 'envd' => 0.0,
            default => 22.0,
        };
    }

    /**
     * Расчет сроков оплаты
     */
    protected function calculateDueDate(Order $order, string $scenario): string
    {
        $paymentDeadline = $this->getPaymentDeadlineFromContract($order);

        if ($paymentDeadline) {
            return now()->addDays($paymentDeadline)->format('d.m.Y');
        }

        $days = match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => 3,
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => 10,
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => 7,
            default => 5,
        };

        return now()->addDays($days)->format('d.m.Y');
    }

    /**
     * Получение срока оплаты из договора
     */
    protected function getPaymentDeadlineFromContract(Order $order): ?int
    {
        try {
            $platformCompany = Company::where('is_platform', true)->first();

            // ПРИОРИТЕТ 1: Договор с арендатором
            $contractWithLessee = Contract::where('company_id', $platformCompany->id)
                ->where('counterparty_company_id', $order->lessee_company_id)
                ->where('counterparty_type', 'lessee')
                ->where('is_active', true)
                ->where(function($query) use ($order) {
                    $query->whereNull('order_id')->orWhere('order_id', $order->id);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($contractWithLessee && !empty($contractWithLessee->payment_deadline)) {
                return (int)$contractWithLessee->payment_deadline;
            }

            // ПРИОРИТЕТ 2: Общий договор с арендатором
            $contractForLessee = Contract::where('counterparty_company_id', $order->lessee_company_id)
                ->where('is_active', true)
                ->first();

            if ($contractForLessee && !empty($contractForLessee->payment_deadline)) {
                return (int)$contractForLessee->payment_deadline;
            }

        } catch (\Exception $e) {
            Log::error('Ошибка получения срока оплаты из договора', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Получение данных договора для счета
     */
    protected function getContractDataForInvoice(Order $order): array
    {
        try {
            $platformCompany = Company::where('is_platform', true)->first();

            $contract = Contract::where('company_id', $platformCompany->id)
                ->where('counterparty_company_id', $order->lessee_company_id)
                ->where('counterparty_type', 'lessee')
                ->where('is_active', true)
                ->where(function($query) use ($order) {
                    $query->whereNull('order_id')->orWhere('order_id', $order->id);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($contract) {
                return [
                    'contract_number' => $contract->number,
                    'contract_date' => $contract->start_date ? $contract->start_date->format('d.m.Y') :
                                      ($contract->created_at ? $contract->created_at->format('d.m.Y') : ''),
                ];
            }

            if (!empty($order->contract_number)) {
                return [
                    'contract_number' => $order->contract_number,
                    'contract_date' => $order->contract_date ? $order->contract_date->format('d.m.Y') : '',
                ];
            }

        } catch (\Exception $e) {
            Log::error('Ошибка получения данных договора', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        return [
            'contract_number' => 'Не указан',
            'contract_date' => 'Не указана',
        ];
    }

    /**
     * Парсинг даты оплаты
     */
    protected function parseDueDate($dueDate): Carbon
    {
        if (is_array($dueDate)) {
            $dueDate = $dueDate[0] ?? $dueDate['date'] ?? $dueDate['due_date'] ?? now()->format('d.m.Y');
        }

        if ($dueDate instanceof Carbon) {
            return $dueDate;
        }

        if (is_string($dueDate)) {
            try {
                return Carbon::createFromFormat('d.m.Y', $dueDate);
            } catch (\Exception $e) {
                Log::error('Ошибка парсинга due_date', ['due_date' => $dueDate, 'error' => $e->getMessage()]);
            }
        }

        return now()->addDays(10);
    }

    /**
     * Безопасное форматирование даты
     */
    protected function safeDateFormat($date, string $format = 'd.m.Y'): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }
        return $date->format($format);
    }

    /**
     * Расчет периода аренды в днях
     */
    protected function calculateRentalPeriodDays(Order $order): int
    {
        if (!$order->start_date || !$order->end_date) {
            return 0;
        }

        try {
            $start = $order->start_date instanceof Carbon ? $order->start_date : Carbon::parse($order->start_date);
            $end = $order->end_date instanceof Carbon ? $order->end_date : Carbon::parse($order->end_date);
            return $start->diffInDays($end) + 1;
        } catch (\Exception $e) {
            Log::error('Ошибка расчета периода аренды', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Генерация описания периода аренды
     */
    protected function generateRentalPeriodDescription(Order $order): string
    {
        if (!$order->start_date || !$order->end_date) {
            return 'Период аренды не указан';
        }

        $startDate = $this->safeDateFormat($order->start_date);
        $endDate = $this->safeDateFormat($order->end_date);
        $days = $this->calculateRentalPeriodDays($order);

        return "с {$startDate} по {$endDate} ({$days} " . $this->getDaysWord($days) . ")";
    }

    /**
     * Получение правильной формы слова "день"
     */
    protected function getDaysWord(int $days): string
    {
        $lastDigit = $days % 10;
        $lastTwoDigits = $days % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
            return 'дней';
        }

        return match($lastDigit) {
            1 => 'день',
            2, 3, 4 => 'дня',
            default => 'дней',
        };
    }

    /**
     * Генерация упрощенного номера счета
     */
    protected function generateSimpleInvoiceNumber(Order $order, string $scenario): string
    {
        $currentYear = date('Y');
        $lastInvoice = Invoice::whereYear('created_at', $currentYear)->orderBy('id', 'desc')->first();
        $sequenceNumber = $lastInvoice ? (intval(substr($lastInvoice->number, -4)) + 1) : 1;

        $prefix = match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => 'СЧ-АВ',
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => 'СЧ-ПП',
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => 'СЧ-ЧАСТ',
            default => 'СЧ',
        };

        return "{$prefix}/{$currentYear}/" . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Получение названия сценария
     */
    protected function getScenarioName(string $scenario): string
    {
        return match($scenario) {
            DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER => 'Предоплатный счет к заказу',
            DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD => 'Постоплатный счет к УПД',
            DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT => 'Счет на частичную оплату',
            default => 'Счет на оплату',
        };
    }

    /**
     * Конвертация суммы в слова
     */
    protected function amountToWords(?float $amount): string
    {
        if ($amount === null || !is_numeric($amount)) {
            $amount = 0.0;
        }

        $amount = round($amount, 2);
        $num = number_format($amount, 2, '.', '');
        $parts = explode('.', $num);
        $rub = $parts[0] ?? '0';
        $kop = $parts[1] ?? '00';

        $rubWords = $this->numberToWords((int)$rub);
        $kopWords = $this->numberToWords((int)$kop);

        $rubText = $this->getCurrencyWord((int)$rub, 'rub');
        $kopText = $this->getCurrencyWord((int)$kop, 'kop');

        return "{$rubWords} {$rubText} {$kopWords} {$kopText}";
    }

    /**
     * Конвертация числа в слова
     */
    protected function numberToWords(int $number): string
    {
        if ($number < 0) $number = abs($number);

        $words = [
            0 => 'ноль', 1 => 'один', 2 => 'два', 3 => 'три', 4 => 'четыре', 5 => 'пять',
            6 => 'шесть', 7 => 'семь', 8 => 'восемь', 9 => 'девять', 10 => 'десять',
            11 => 'одиннадцать', 12 => 'двенадцать', 13 => 'тринадцать', 14 => 'четырнадцать',
            15 => 'пятнадцать', 16 => 'шестнадцать', 17 => 'семнадцать', 18 => 'восемнадцать',
            19 => 'девятнадцать', 20 => 'двадцать', 30 => 'тридцать', 40 => 'сорок',
            50 => 'пятьдесят', 60 => 'шестьдесят', 70 => 'семьдесят', 80 => 'восемьдесят',
            90 => 'девяносто', 100 => 'сто', 200 => 'двести', 300 => 'триста', 400 => 'четыреста',
            500 => 'пятьсот', 600 => 'шестьсот', 700 => 'семьсот', 800 => 'восемьсот', 900 => 'девятьсот'
        ];

        if ($number === 0) return $words[0];

        $result = '';

        // Миллионы
        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $result .= $this->numberToWords($millions) . ' миллион ';
            $number %= 1000000;
            $result = $this->fixMillionEnding($result, $millions);
        }

        // Тысячи
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $thousandWord = $this->getThousandWord($thousands);
            $result .= $this->numberToWords($thousands) . ' ' . $thousandWord . ' ';
            $number %= 1000;
        }

        // Сотни
        if ($number >= 100) {
            $hundreds = floor($number / 100) * 100;
            $result .= $words[$hundreds] . ' ';
            $number %= 100;
        }

        // Десятки и единицы
        if ($number > 0) {
            if ($number <= 20) {
                $result .= $words[$number];
            } else {
                $tens = floor($number / 10) * 10;
                $units = $number % 10;
                $result .= $words[$tens];
                if ($units > 0) {
                    $result .= ' ' . $words[$units];
                }
            }
        }

        return trim(preg_replace('/\s+/', ' ', $result));
    }

    /**
     * Исправление окончания для миллионов
     */
    protected function fixMillionEnding(string $text, int $number): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
            return str_replace('миллион', 'миллионов', $text);
        }

        $replacement = match($lastDigit) {
            1 => 'миллион', 2, 3, 4 => 'миллиона', default => 'миллионов',
        };

        return str_replace('миллион', $replacement, $text);
    }

    /**
     * Получение правильной формы слова "тысяча"
     */
    protected function getThousandWord(int $number): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) return 'тысяч';

        return match($lastDigit) {
            1 => 'тысяча', 2, 3, 4 => 'тысячи', default => 'тысяч',
        };
    }

    /**
     * Получение правильной формы валюты
     */
    protected function getCurrencyWord(int $number, string $currency): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($currency === 'rub') {
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) return 'рублей';
            return match($lastDigit) { 1 => 'рубль', 2, 3, 4 => 'рубля', default => 'рублей' };
        } else {
            if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) return 'копеек';
            return match($lastDigit) { 1 => 'копейка', 2, 3, 4 => 'копейки', default => 'копеек' };
        }
    }

    /**
     * Получить список доступных сценариев для заказа
     */
    public function getAvailableScenarios(Order $order): array
    {
        $scenarios = [];

        if (in_array($order->status, ['pending', 'active'])) {
            $scenarios[DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER] = 'Предоплатный счет к заказу';
            $scenarios[DocumentTemplate::INVOICE_SCENARIO_PARTIAL_PAYMENT] = 'Счет на частичную оплату';
        }

        $hasSuitableUpd = $order->upds()
            ->where('lessor_company_id', Company::where('is_platform', true)->first()->id)
            ->whereIn('status', ['pending', 'sent', 'accepted', 'processed'])
            ->exists();

        if ($hasSuitableUpd) {
            $scenarios[DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD] = 'Постоплатный счет к УПД';
        }

        return $scenarios;
    }

    /**
     * Удалить счет и связанные файлы
     */
    public function deleteInvoice(Invoice $invoice): bool
    {
        try {
            DB::transaction(function () use ($invoice) {
                if ($invoice->file_path && Storage::exists($invoice->file_path)) {
                    Storage::delete($invoice->file_path);
                }
                $invoice->items()->delete();
                $invoice->delete();
            });

            Log::info('Счет удален', ['invoice_id' => $invoice->id]);
            return true;

        } catch (\Exception $e) {
            Log::error('Ошибка удаления счета', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
