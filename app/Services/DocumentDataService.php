<?php

namespace App\Services;

use App\Models\Upd;
use App\Models\Waybill;
use App\Models\Invoice;
use App\Models\TransportInvoice;
use App\Models\CompletionAct;
use App\Models\Esm7;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DocumentDataService
{
    public function prepareDocumentData($document, string $documentType)
    {
        return match($documentType) {
            'упд' => $this->prepareUpdData($document),
            'транспортная_накладная' => $this->prepareTransportInvoiceData($document),
            'акт_выполненных_работ' => $this->prepareCompletionActData($document),
            'эсм_7' => $this->prepareEsm7Data($document),
            'счет_фактура' => $this->prepareInvoiceData($document),
            'счет_на_оплату' => $this->prepareInvoiceData($document),
            'путевой_лист' => $this->prepareWaybillData($document),
            'акт' => $this->prepareCompletionActData($document),
            'акт_сверки' => $this->prepareReconciliationActData($document),
            'договор' => $this->prepareContractData($document),
            default => throw new \Exception("Неизвестный тип документа: {$documentType}")
        };
    }

    protected function prepareUpdData(Upd $upd)
    {
        // Убедимся, что отношения загружены (УБИРАЕМ order.equipment)
        if (!$upd->relationLoaded('order')) {
            $upd->load([
                'order.items.equipment', // ТОЛЬКО через позиции заказа
            ]);
        }
        if (!$upd->relationLoaded('waybill')) {
            $upd->load('waybill.equipment');
        }
        if (!$upd->relationLoaded('completionAct')) {
            $upd->load('completionAct');
        }
        if (!$upd->relationLoaded('items')) {
            $upd->load('items');
        }

        $platformCompany = Company::where('is_platform', true)->first();
        $lesseeCompany = $upd->lesseeCompany;

        // ПРОВЕРЯЕМ - генерируем только ИСХОДЯЩИЕ УПД (Платформа → Арендатор)
        $isOutgoingUpd = $upd->lessor_company_id === $platformCompany->id;

        if (!$isOutgoingUpd) {
            throw new \Exception('Генерация УПД возможна только для исходящих документов (Платформа → Арендатор). Входящие УПД должны загружаться готовыми файлами.');
        }

        // Получаем связанные данные для формирования полных названий
        $waybill = $upd->waybill;
        $order = $upd->order;
        $completionAct = $upd->completionAct;

        // Формируем строку периода
        $periodString = '';
        if ($upd->service_period_start && $upd->service_period_end) {
            $periodString = $upd->service_period_start->format('d.m.Y') . ' по ' . $upd->service_period_end->format('d.m.Y');
        }

        // Получаем данные об оборудовании и технике
        $equipmentData = $this->getEquipmentDataForUpd($upd, $waybill, $order);

        // Получаем данные договора
        $contractData = $this->getContractData($upd);

        // Формируем полное описание услуги для использования в шаблоне
        $serviceDescription = "Аренда ";
        if (!empty($equipmentData['name'])) {
            $serviceDescription .= $equipmentData['name'];
            if (!empty($equipmentData['model'])) {
                $serviceDescription .= " " . $equipmentData['model'];
            }
        } else {
            $serviceDescription .= "техники";
        }

        if (!empty($equipmentData['vehicle_number'])) {
            $serviceDescription .= " (гос. номер: " . $equipmentData['vehicle_number'] . ")";
        }

        $serviceDescription .= " за период " . $upd->service_period_start->format('d.m.Y') . " - " . $upd->service_period_end->format('d.m.Y');

        Log::info('Подготовка данных УПД', [
            'upd_id' => $upd->id,
            'equipment_data' => $equipmentData,
            'service_description' => $serviceDescription,
            'items_count' => $upd->items->count()
        ]);

        return [
            // Основные данные УПД
            'upd' => [
                'number' => $upd->number,
                'date' => $upd->issue_date ? $upd->issue_date->format('d.m.Y') : '',
                'contract_number' => $contractData['contract_number'],
                'contract_date' => $contractData['contract_date'],
                'shipment_date' => $upd->service_period_start ? $upd->service_period_start->format('d.m.Y') : '',
                'total_without_vat' => $upd->amount,
                'total_vat' => $upd->tax_amount,
                'total_with_vat' => $upd->total_amount,
                'period' => $periodString,
                'service_description' => $serviceDescription,
            ],

            // ДЛЯ ШАБЛОНА - продавец и покупатель
            'seller' => [
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

            'buyer' => [
                'name' => $lesseeCompany->legal_name,
                'legal_name' => $lesseeCompany->legal_name,
                'address' => $lesseeCompany->legal_address,
                'inn' => $lesseeCompany->inn,
                'kpp' => $lesseeCompany->kpp,
                'inn_kpp' => $lesseeCompany->inn . ' / ' . $lesseeCompany->kpp,
                'bank_name' => $lesseeCompany->bank_name,
                'bik' => $lesseeCompany->bik,
                'account_number' => $lesseeCompany->bank_account,
                'correspondent_account' => $lesseeCompany->correspondent_account,
            ],

            'equipment' => $equipmentData,
            'period_full' => $periodString,
            'service_description_full' => $serviceDescription,

            'items' => $upd->items->map(function ($item, $index) use ($periodString, $equipmentData, $upd, $serviceDescription) {
                // ФОРМИРУЕМ ПОЛНОЕ НАЗВАНИЕ БЕЗ ДУБЛИРОВАНИЯ ПЕРИОДА
                $fullItemName = $this->generateFullItemName($item, $periodString, $equipmentData, $upd, $index);

                Log::debug('Формирование позиции УПД', [
                    'item_id' => $item->id,
                    'original_name' => $item->name,
                    'full_name' => $fullItemName,
                    'vehicle_number' => $equipmentData['vehicle_number'] ?? 'не найден'
                ]);

                return [
                    'code' => $item->code ?? ($index + 1),
                    'name' => $fullItemName,
                    'name_short' => $item->name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'amount' => $item->amount,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $item->vat_amount,
                    'total_with_vat' => $item->amount + $item->vat_amount,
                    'total_without_vat' => $item->amount,
                    'total' => $item->amount + $item->vat_amount,
                    'period' => $periodString,
                    'index' => $index + 1,
                    'equipment_name' => $equipmentData['name'] ?? '',
                    'equipment_model' => $equipmentData['model'] ?? '',
                    'vehicle_number' => $equipmentData['vehicle_number'] ?? '',
                    'period_text' => $periodString,
                    'service_description' => $serviceDescription,
                ];
            })->toArray()
        ];
    }

    /**
     * Получает данные об оборудовании для УПД
     */
    protected function getEquipmentDataForUpd(Upd $upd, $waybill, $order): array
    {
        $equipmentData = [
            'name' => '',
            'model' => '',
            'vehicle_number' => '',
            'full_description' => ''
        ];

        try {
            Log::debug('=== НАЧАЛО ПОИСКА ДАННЫХ ОБОРУДОВАНИЯ ===', [
                'upd_id' => $upd->id,
                'waybill_id' => $waybill->id ?? 'NOT_FOUND',
                'order_id' => $order->id ?? 'NOT_FOUND'
            ]);

            // ПЕРВЫЙ ПРИОРИТЕТ: оборудование из путевого листа
            if ($waybill) {
                Log::debug('Путевой лист найден', [
                    'waybill_id' => $waybill->id,
                    'license_plate' => $waybill->license_plate ?? 'NULL',
                    'vehicle_model' => $waybill->vehicle_model ?? 'NULL',
                    'has_equipment_relation' => $waybill->relationLoaded('equipment'),
                    'equipment_id' => $waybill->equipment_id ?? 'NULL'
                ]);

                // Если есть связанное оборудование в путевом листе
                if ($waybill->equipment) {
                    $equipment = $waybill->equipment;
                    Log::debug('Оборудование из путевого листа', [
                        'equipment_id' => $equipment->id,
                        'title' => $equipment->title ?? 'NULL',
                        'model' => $equipment->model ?? 'NULL',
                        'license_plate' => $equipment->license_plate ?? 'NULL'
                    ]);

                    if (!empty($equipment->license_plate)) {
                        $equipmentData['vehicle_number'] = $equipment->license_plate;
                        Log::debug('Гос. номер найден в оборудовании путевого листа', [
                            'license_plate' => $equipment->license_plate
                        ]);
                    }

                    if (!empty($equipment->title)) {
                        $equipmentData['name'] = $equipment->title;
                    }

                    if (!empty($equipment->model)) {
                        $equipmentData['model'] = $equipment->model;
                    }
                } else {
                    Log::debug('В путевом листе нет связанного оборудования');
                }

                // ВТОРОЙ ПРИОРИТЕТ: гос. номер из самого путевого листа
                if (empty($equipmentData['vehicle_number']) && !empty($waybill->license_plate)) {
                    $equipmentData['vehicle_number'] = $waybill->license_plate;
                    Log::debug('Гос. номер найден в путевом листе', [
                        'license_plate' => $waybill->license_plate
                    ]);
                }
            } else {
                Log::warning('Путевой лист не найден для УПД', ['upd_id' => $upd->id]);
            }

            // ТРЕТИЙ ПРИОРИТЕТ: оборудование из позиций заказа
            if ((empty($equipmentData['vehicle_number']) || empty($equipmentData['name'])) && $order && $order->relationLoaded('items')) {
                Log::debug('Поиск оборудования в позициях заказа', [
                    'order_id' => $order->id,
                    'items_count' => $order->items->count()
                ]);

                foreach ($order->items as $item) {
                    if ($item->equipment) {
                        $equipment = $item->equipment;
                        Log::debug('Оборудование из позиции заказа', [
                            'item_id' => $item->id,
                            'equipment_id' => $equipment->id,
                            'title' => $equipment->title ?? 'NULL',
                            'model' => $equipment->model ?? 'NULL',
                            'license_plate' => $equipment->license_plate ?? 'NULL'
                        ]);

                        if (empty($equipmentData['vehicle_number']) && !empty($equipment->license_plate)) {
                            $equipmentData['vehicle_number'] = $equipment->license_plate;
                            Log::debug('Гос. номер найден в оборудовании позиции заказа', [
                                'license_plate' => $equipment->license_plate
                            ]);
                        }

                        if (empty($equipmentData['name']) && !empty($equipment->title)) {
                            $equipmentData['name'] = $equipment->title;
                        }

                        if (empty($equipmentData['model']) && !empty($equipment->model)) {
                            $equipmentData['model'] = $equipment->model;
                        }

                        // Если нашли все данные, выходим из цикла
                        if (!empty($equipmentData['vehicle_number']) && !empty($equipmentData['name'])) {
                            break;
                        }
                    } else {
                        Log::debug('Позиция заказа не имеет оборудования', ['item_id' => $item->id]);
                    }
                }
            }

            // УБРАН ЧЕТВЕРТЫЙ ПРИОРИТЕТ - отношения equipment в модели Order не существует

            // Формируем полное описание
            $parts = [];
            if (!empty($equipmentData['name'])) {
                $parts[] = $equipmentData['name'];
            }
            if (!empty($equipmentData['model'])) {
                $parts[] = $equipmentData['model'];
            }
            if (!empty($equipmentData['vehicle_number'])) {
                $parts[] = 'г.р.з. ' . $equipmentData['vehicle_number'];
            }

            $equipmentData['full_description'] = implode(' ', $parts);

            Log::debug('=== ИТОГОВЫЕ ДАННЫЕ ОБОРУДОВАНИЯ ===', $equipmentData);

        } catch (\Exception $e) {
            Log::error('Ошибка получения данных об оборудовании для УПД', [
                'upd_id' => $upd->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $equipmentData;
    }

    /**
     * Публичный метод для получения данных об оборудовании для отображения в интерфейсе
     */
    public function getEquipmentDataForDisplay(Upd $upd): array
    {
        $waybill = $upd->waybill;
        $order = $upd->order;

        return $this->getEquipmentDataForUpd($upd, $waybill, $order);
    }

    /**
     * Публичный метод для генерации названия позиции для отображения в интерфейсе
     */
    public function generateItemNameForDisplay($item, Upd $upd, array $equipmentData, int $index): string
    {
        $periodString = $upd->service_period_start->format('d.m.Y') . ' по ' . $upd->service_period_end->format('d.m.Y');

        return $this->generateFullItemName($item, $periodString, $equipmentData, $upd, $index);
    }

    protected function getContractData(Upd $upd)
    {
        try {
            // Ищем активный договор между компаниями
            $contract = \App\Models\Contract::where('company_id', $upd->lessor_company_id)
                ->where('counterparty_company_id', $upd->lessee_company_id)
                ->where('is_active', true)
                ->where(function($query) use ($upd) {
                    $query->whereNull('order_id')
                        ->orWhere('order_id', $upd->order_id);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            Log::debug('Найденный договор для УПД', [
                'upd_id' => $upd->id,
                'contract_id' => $contract->id ?? 'NOT_FOUND',
                'contract_number' => $contract->number ?? 'NOT_FOUND',
                'contract_start_date' => $contract->start_date ?? 'NOT_FOUND',
                'contract_created_at' => $contract->created_at ?? 'NOT_FOUND'
            ]);

            if ($contract) {
                return [
                    'contract_number' => $contract->number,
                    'contract_date' => $contract->start_date ? $contract->start_date->format('d.m.Y') : ($contract->created_at ? $contract->created_at->format('d.m.Y') : ''),
                ];
            }

            // Если договор не найден, используем данные из УПД или заказа
            if (!empty($upd->contract_number)) {
                return [
                    'contract_number' => $upd->contract_number,
                    'contract_date' => $upd->contract_date ? $upd->contract_date->format('d.m.Y') : '',
                ];
            }

            // Пробуем получить из заказа
            $order = $upd->order;
            if ($order && !empty($order->contract_number)) {
                return [
                    'contract_number' => $order->contract_number,
                    'contract_date' => $order->contract_date ? $order->contract_date->format('d.m.Y') : '',
                ];
            }

        } catch (\Exception $e) {
            Log::error('Ошибка получения данных договора для УПД', [
                'upd_id' => $upd->id,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'contract_number' => 'Не указан',
            'contract_date' => 'Не указана',
        ];
    }

    /**
     * Генерирует полное название позиции с периодом и деталями оборудования
     */
    protected function generateFullItemName($item, string $periodString, array $equipmentData, Upd $upd, int $index): string
    {
        $baseName = $item->name;

        Log::debug('Начало generateFullItemName', [
            'baseName' => $baseName,
            'vehicle_number' => $equipmentData['vehicle_number'] ?? 'не указан',
            'periodString' => $periodString
        ]);

        // Если в названии уже есть плейсхолдеры, заменяем их
        if (strpos($baseName, '{{period}}') !== false) {
            $baseName = str_replace('{{period}}', $periodString, $baseName);
        }

        if (strpos($baseName, '{{equipment}}') !== false) {
            $baseName = str_replace('{{equipment}}', $equipmentData['full_description'] ?? '', $baseName);
        }

        // ОСНОВНОЕ ИСПРАВЛЕНИЕ: Замена пустого гос. номера на реальный
        if (!empty($equipmentData['vehicle_number'])) {
            // Вариант 1: Замена шаблона с пустыми скобками
            if (strpos($baseName, '(гос. номер: )') !== false) {
                $baseName = str_replace('(гос. номер: )', '(гос. номер: ' . $equipmentData['vehicle_number'] . ')', $baseName);
                Log::debug('Гос. номер добавлен в название (замена пустого)', [
                    'vehicle_number' => $equipmentData['vehicle_number'],
                    'new_name' => $baseName
                ]);
            }
            // Вариант 2: Добавление гос. номера если его нет вообще
            elseif (strpos($baseName, 'гос. номер') === false) {
                // Ищем место для вставки - обычно перед "за период"
                if (strpos($baseName, 'за период') !== false) {
                    $baseName = str_replace('за период', '(гос. номер: ' . $equipmentData['vehicle_number'] . ') за период', $baseName);
                } else {
                    // Если "за период" нет, добавляем в конец
                    $baseName .= ' (гос. номер: ' . $equipmentData['vehicle_number'] . ')';
                }
                Log::debug('Гос. номер добавлен в название (новый)', [
                    'vehicle_number' => $equipmentData['vehicle_number'],
                    'new_name' => $baseName
                ]);
            }
        }

        // Если название короткое (просто "Аренда" или "Услуги"), формируем полное описание
        if (in_array(mb_strtolower(trim($baseName)), ['аренда', 'услуги', 'аренда оборудования', 'оказание услуг'])) {
            $parts = [];

            // Добавляем тип услуги
            $parts[] = 'Аренда';

            // Добавляем описание оборудования если есть
            if (!empty($equipmentData['full_description'])) {
                $parts[] = $equipmentData['full_description'];
            } else {
                $parts[] = 'оборудования';
            }

            // Добавляем гос. номер если есть
            if (!empty($equipmentData['vehicle_number'])) {
                $parts[] = '(гос. номер: ' . $equipmentData['vehicle_number'] . ')';
            }

            // Добавляем период ТОЛЬКО ОДИН РАЗ
            if (!empty($periodString)) {
                $parts[] = 'за период с ' . $periodString;
            }

            $result = implode(' ', $parts);
            Log::debug('Сформировано полное описание для короткого названия', [
                'original' => $baseName,
                'result' => $result
            ]);
            return $result;
        }

        Log::debug('Конец generateFullItemName', ['result' => $baseName]);

        return $baseName;
    }

    protected function prepareTransportInvoiceData(TransportInvoice $transportInvoice)
    {
        $platformCompany = Company::where('is_platform', true)->first();

        return [
            'transport_invoice' => [
                'number' => $transportInvoice->number,
                'date' => $transportInvoice->date ? $transportInvoice->date->format('d.m.Y') : '',
                'departure_date' => $transportInvoice->departure_date ? $transportInvoice->departure_date->format('d.m.Y') : '',
                'delivery_date' => $transportInvoice->delivery_date ? $transportInvoice->delivery_date->format('d.m.Y') : '',
                'departure_point' => $transportInvoice->departure_point,
                'destination_point' => $transportInvoice->destination_point,
                'distance' => $transportInvoice->distance,
                'cargo_weight' => $transportInvoice->cargo_weight,
                'cargo_description' => $transportInvoice->cargo_description,
                'vehicle_number' => $transportInvoice->vehicle_number,
                'driver_name' => $transportInvoice->driver_name,
                'driver_license' => $transportInvoice->driver_license,
            ],
            'carrier' => [
                'name' => $transportInvoice->carrier->legal_name ?? '',
                'address' => $transportInvoice->carrier->legal_address ?? '',
                'inn' => $transportInvoice->carrier->inn ?? '',
                'kpp' => $transportInvoice->carrier->kpp ?? '',
            ],
            'sender' => [
                'name' => $transportInvoice->sender->legal_name ?? '',
                'address' => $transportInvoice->sender->legal_address ?? '',
                'inn' => $transportInvoice->sender->inn ?? '',
                'kpp' => $transportInvoice->sender->kpp ?? '',
            ],
            'recipient' => [
                'name' => $transportInvoice->recipient->legal_name ?? '',
                'address' => $transportInvoice->recipient->legal_address ?? '',
                'inn' => $transportInvoice->recipient->inn ?? '',
                'kpp' => $transportInvoice->recipient->kpp ?? '',
            ],
            'platform' => [
                'name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
                'bank_name' => $platformCompany->bank_name,
                'bik' => $platformCompany->bik,
                'account_number' => $platformCompany->bank_account,
            ]
        ];
    }

    protected function prepareCompletionActData(CompletionAct $completionAct)
    {
        $platformCompany = Company::where('is_platform', true)->first();

        // Получаем связанный заказ, если есть
        $order = $completionAct->order;

        return [
            'completion_act' => [
                'number' => $completionAct->number,
                'date' => $completionAct->date ? $completionAct->date->format('d.m.Y') : '',
                'period_start' => $completionAct->period_start ? $completionAct->period_start->format('d.m.Y') : '',
                'period_end' => $completionAct->period_end ? $completionAct->period_end->format('d.m.Y') : '',
                'work_description' => $completionAct->work_description,
                'total_amount' => $completionAct->total_amount,
                'act_date' => $completionAct->act_date ? $completionAct->act_date->format('d.m.Y') : '',
            ],
            'customer' => [
                'name' => $completionAct->customer->legal_name ?? $order->lesseeCompany->legal_name ?? '',
                'address' => $completionAct->customer->legal_address ?? $order->lesseeCompany->legal_address ?? '',
                'inn' => $completionAct->customer->inn ?? $order->lesseeCompany->inn ?? '',
                'kpp' => $completionAct->customer->kpp ?? $order->lesseeCompany->kpp ?? '',
                'representative' => $completionAct->customer_representative,
            ],
            'contractor' => [
                'name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
                'representative' => $completionAct->contractor_representative,
                'bank_name' => $platformCompany->bank_name,
                'bik' => $platformCompany->bik,
                'account_number' => $platformCompany->bank_account,
            ],
            'order' => [
                'number' => $order->number ?? '',
                'date' => $order->created_at ? $order->created_at->format('d.m.Y') : '',
            ],
            'items' => $completionAct->items->map(function($item) {
                return [
                    'name' => $item->name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'amount' => $item->amount,
                ];
            })->toArray()
        ];
    }

    protected function prepareEsm7Data(Esm7 $esm7)
    {
        $platformCompany = Company::where('is_platform', true)->first();

        return [
            'esm7' => [
                'number' => $esm7->number,
                'date' => $esm7->date ? $esm7->date->format('d.m.Y') : '',
                'contract_number' => $esm7->contract_number,
                'contract_date' => $esm7->contract_date ? $esm7->contract_date->format('d.m.Y') : '',
                'work_period' => $esm7->work_period,
                'total_cost' => $esm7->total_cost,
            ],
            'customer' => [
                'name' => $esm7->customer->legal_name ?? '',
                'address' => $esm7->customer->legal_address ?? '',
                'inn' => $esm7->customer->inn ?? '',
                'kpp' => $esm7->customer->kpp ?? '',
            ],
            'contractor' => [
                'name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
                'bank_name' => $platformCompany->bank_name,
                'bik' => $platformCompany->bik,
                'account_number' => $platformCompany->bank_account,
            ],
            'work_items' => $esm7->workItems->map(function($item) {
                return [
                    'code' => $item->code,
                    'name' => $item->name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'cost' => $item->cost,
                ];
            })->toArray()
        ];
    }

    protected function prepareInvoiceData(Invoice $invoice)
    {
        $platformCompany = Company::where('is_platform', true)->first();
        $payerCompany = $invoice->payerCompany;

        // Получаем данные об оборудовании для подстановки в названия
        $equipmentData = $this->getEquipmentDataForInvoiceDisplay($invoice);

        return [
            'invoice' => [
                'number' => $invoice->number,
                'date' => $invoice->date ? $invoice->date->format('d.m.Y') : '',
                'due_date' => $invoice->due_date ? $invoice->due_date->format('d.m.Y') : '',
                'total_amount' => $invoice->total_amount,
                'currency' => $invoice->currency ?? 'RUB',
            ],
            'supplier' => [
                'name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
                'bank_name' => $platformCompany->bank_name,
                'bik' => $platformCompany->bik,
                'account_number' => $platformCompany->bank_account,
                'correspondent_account' => $platformCompany->correspondent_account,
            ],
            'payer' => [
                'name' => $payerCompany->legal_name,
                'address' => $payerCompany->legal_address,
                'inn' => $payerCompany->inn,
                'kpp' => $payerCompany->kpp,
            ],
            'invoice_items' => $invoice->items->map(function($item) use ($equipmentData) {
                // ПОДСТАВЛЯЕМ ГОС. НОМЕР В НАЗВАНИЕ ПОЗИЦИИ
                $itemName = $this->fixVehicleNumberInItemName($item->name, $equipmentData);

                return [
                    'name' => $itemName,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'price' => $item->price,
                    'amount' => $item->amount,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $item->vat_amount,
                ];
            })->toArray(),
            'equipment_data' => $equipmentData, // Добавляем данные об оборудовании для представления
        ];
    }

    /**
     * Подготовка данных счета для отображения на странице - ПУБЛИЧНЫЙ МЕТОД
     */
    public function prepareInvoiceDataForDisplay(Invoice $invoice): array
    {
        $platformCompany = Company::where('is_platform', true)->first();

        // ИСПРАВЛЕНИЕ: Используем правильное отношение - company вместо payerCompany
        $payerCompany = $invoice->company;

        // Получаем данные об оборудовании для подстановки в названия
        $equipmentData = $this->getEquipmentDataForInvoiceDisplay($invoice);

        // Проверяем, что компании существуют
        if (!$platformCompany) {
            Log::error('Платформенная компания не найдена', ['invoice_id' => $invoice->id]);
            $platformCompany = new Company(); // Создаем пустой объект для избежания ошибок
        }

        if (!$payerCompany) {
            Log::warning('Компания-плательщик не найдена для счета', ['invoice_id' => $invoice->id]);
            $payerCompany = new Company(); // Создаем пустой объект для избежания ошибок
        }

        return [
            'invoice' => [
                'number' => $invoice->number,
                'date' => $invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : '', // Исправлено: issue_date вместо date
                'due_date' => $invoice->due_date ? $invoice->due_date->format('d.m.Y') : '',
                'total_amount' => $invoice->amount, // Исправлено: amount вместо total_amount
                'currency' => 'RUB', // По умолчанию
            ],
            'supplier' => [
                'name' => $platformCompany->legal_name ?? '',
                'address' => $platformCompany->legal_address ?? '',
                'inn' => $platformCompany->inn ?? '',
                'kpp' => $platformCompany->kpp ?? '',
                'bank_name' => $platformCompany->bank_name ?? '',
                'bik' => $platformCompany->bik ?? '',
                'account_number' => $platformCompany->bank_account ?? '',
                'correspondent_account' => $platformCompany->correspondent_account ?? '',
            ],
            'payer' => [
                'name' => $payerCompany->legal_name ?? '',
                'address' => $payerCompany->legal_address ?? '',
                'inn' => $payerCompany->inn ?? '',
                'kpp' => $payerCompany->kpp ?? '',
                'bank_name' => $payerCompany->bank_name ?? '',
                'bik' => $payerCompany->bik ?? '',
                'account_number' => $payerCompany->bank_account ?? '',
                'correspondent_account' => $payerCompany->correspondent_account ?? '',
            ],
            'invoice_items' => $invoice->items->map(function($item) use ($equipmentData) {
                // ПОДСТАВЛЯЕМ ГОС. НОМЕР В НАЗВАНИЕ ПОЗИЦИИ
                $itemName = $this->fixVehicleNumberInItemName($item->name, $equipmentData);

                return [
                    'name' => $itemName,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'price' => $item->price,
                    'amount' => $item->amount,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $item->vat_amount,
                ];
            })->toArray(),
            'equipment_data' => $equipmentData,
        ];
    }


    /**
     * Получает данные об оборудовании для отображения на странице счета
     */
    protected function getEquipmentDataForInvoiceDisplay(Invoice $invoice): array
    {
        $equipmentData = [
            'vehicle_number' => '',
            'name' => '',
            'model' => '',
            'full_description' => ''
        ];

        try {
            // Пытаемся получить данные из связанного УПД
            if ($invoice->upd) {
                $upd = $invoice->upd;
                if (!$upd->relationLoaded('waybill')) {
                    $upd->load('waybill.equipment');
                }

                if ($upd->waybill) {
                    if (!empty($upd->waybill->license_plate)) {
                        $equipmentData['vehicle_number'] = $upd->waybill->license_plate;
                    }

                    if ($upd->waybill->equipment) {
                        $equipment = $upd->waybill->equipment;
                        if (empty($equipmentData['vehicle_number']) && !empty($equipment->license_plate)) {
                            $equipmentData['vehicle_number'] = $equipment->license_plate;
                        }
                        if (!empty($equipment->title)) $equipmentData['name'] = $equipment->title;
                        if (!empty($equipment->model)) $equipmentData['model'] = $equipment->model;
                    }
                }
            }

            // Пытаемся получить из заказа
            if (empty($equipmentData['vehicle_number']) && $invoice->order) {
                $order = $invoice->order;
                if (!$order->relationLoaded('items')) {
                    $order->load('items.equipment');
                }

                foreach ($order->items as $item) {
                    if ($item->equipment && !empty($item->equipment->license_plate)) {
                        $equipmentData['vehicle_number'] = $item->equipment->license_plate;
                        break;
                    }
                }
            }

            // Формируем полное описание
            $parts = [];
            if (!empty($equipmentData['name'])) $parts[] = $equipmentData['name'];
            if (!empty($equipmentData['model'])) $parts[] = $equipmentData['model'];
            if (!empty($equipmentData['vehicle_number'])) $parts[] = 'г.р.з. ' . $equipmentData['vehicle_number'];
            $equipmentData['full_description'] = implode(' ', $parts);

        } catch (\Exception $e) {
            Log::error('Ошибка получения данных об оборудовании для отображения счета', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }

        return $equipmentData;
    }

    /**
     * Исправляет название позиции, подставляя гос. номер
     */
    protected function fixVehicleNumberInItemName(string $itemName, array $equipmentData): string
    {
        $vehicleNumber = $equipmentData['vehicle_number'] ?? '';

        if (empty($vehicleNumber)) {
            return $itemName;
        }

        // Заменяем пустой плейсхолдер гос. номера
        if (strpos($itemName, '(гос. номер: )') !== false) {
            return str_replace('(гос. номер: )', '(гос. номер: ' . $vehicleNumber . ')', $itemName);
        }

        // Добавляем гос. номер, если его нет
        if (strpos($itemName, 'гос. номер') === false) {
            if (strpos($itemName, 'за период') !== false) {
                return str_replace('за период', '(гос. номер: ' . $vehicleNumber . ') за период', $itemName);
            } else {
                return $itemName . ' (гос. номер: ' . $vehicleNumber . ')';
            }
        }

        return $itemName;
    }

    protected function prepareWaybillData(Waybill $waybill)
    {
        $platformCompany = Company::where('is_platform', true)->first();

        return [
            'waybill' => [
                'number' => $waybill->number,
                'date' => $waybill->date ? $waybill->date->format('d.m.Y') : '',
                'vehicle_number' => $waybill->vehicle_number,
                'vehicle_model' => $waybill->vehicle_model,
                'driver_name' => $waybill->driver_name,
                'driver_license' => $waybill->driver_license,
                'route_start' => $waybill->route_start,
                'route_end' => $waybill->route_end,
                'distance_planned' => $waybill->distance_planned,
                'distance_actual' => $waybill->distance_actual,
                'odometer_start' => $waybill->odometer_start,
                'odometer_end' => $waybill->odometer_end,
                'fuel_start' => $waybill->fuel_start,
                'fuel_end' => $waybill->fuel_end,
                'fuel_issued' => $waybill->fuel_issued,
                'fuel_consumption' => $waybill->fuel_consumption,
                'time_departure' => $waybill->time_departure,
                'time_return' => $waybill->time_return,
                'duration' => $waybill->duration,
            ],
            'carrier' => [
                'name' => $waybill->carrier->legal_name ?? $platformCompany->legal_name,
                'address' => $waybill->carrier->legal_address ?? $platformCompany->legal_address,
                'inn' => $waybill->carrier->inn ?? $platformCompany->inn,
                'kpp' => $waybill->carrier->kpp ?? $platformCompany->kpp,
            ],
            'client' => [
                'name' => $waybill->client->legal_name ?? '',
                'address' => $waybill->client->legal_address ?? '',
            ]
        ];
    }

    protected function prepareReconciliationActData($reconciliationAct)
    {
        $platformCompany = Company::where('is_platform', true)->first();
        $counterparty = $reconciliationAct->counterparty;

        $periodStart = $reconciliationAct->period_start ? $reconciliationAct->period_start->format('d.m.Y') : '';
        $periodEnd = $reconciliationAct->period_end ? $reconciliationAct->period_end->format('d.m.Y') : '';
        $period = $periodStart && $periodEnd ? "за период с {$periodStart} по {$periodEnd}" : "";

        return [
            'reconciliation_act' => [
                'number' => $reconciliationAct->number,
                'date' => $reconciliationAct->date ? $reconciliationAct->date->format('d.m.Y') : '',
                'period' => $period,
                'balance_start' => $reconciliationAct->balance_start,
                'balance_end' => $reconciliationAct->balance_end,
                'debit_turnover' => $reconciliationAct->debit_turnover,
                'credit_turnover' => $reconciliationAct->credit_turnover,
            ],
            'company' => [
                'name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
            ],
            'counterparty' => [
                'name' => $counterparty->legal_name,
                'address' => $counterparty->legal_address,
                'inn' => $counterparty->inn,
                'kpp' => $counterparty->kpp,
            ],
            'operations' => $reconciliationAct->operations->map(function($operation) {
                return [
                    'date' => $operation->date ? $operation->date->format('d.m.Y') : '',
                    'document_number' => $operation->document_number,
                    'debit' => $operation->debit,
                    'credit' => $operation->credit,
                ];
            })->toArray()
        ];
    }

    protected function prepareContractData($contract)
    {
        $platformCompany = Company::where('is_platform', true)->first();
        $counterparty = $contract->counterparty;

        return [
            'contract' => [
                'number' => $contract->number,
                'date' => $contract->date ? $contract->date->format('d.m.Y') : '',
                'start_date' => $contract->start_date ? $contract->start_date->format('d.m.Y') : '',
                'end_date' => $contract->end_date ? $contract->end_date->format('d.m.Y') : '',
                'subject' => $contract->subject,
                'amount' => $contract->amount,
                'currency' => $contract->currency ?? 'RUB',
            ],
            'company' => [
                'name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
                'bank_name' => $platformCompany->bank_name,
                'bik' => $platformCompany->bik,
                'account_number' => $platformCompany->bank_account,
                'general_director' => $platformCompany->director_name,
            ],
            'counterparty' => [
                'name' => $counterparty->legal_name,
                'address' => $counterparty->legal_address,
                'inn' => $counterparty->inn,
                'kpp' => $counterparty->kpp,
                'bank_name' => $counterparty->bank_name,
                'bik' => $counterparty->bik,
                'account_number' => $counterparty->bank_account,
                'general_director' => $counterparty->director_name,
            ]
        ];
    }
}
