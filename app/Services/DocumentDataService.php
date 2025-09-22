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
        $platformCompany = Company::where('is_platform', true)->first();
        $lesseeCompany = $upd->lesseeCompany;

        // Формируем строку периода
        $periodString = '';
        if ($upd->service_period_start && $upd->service_period_end) {
            $periodString = $upd->service_period_start->format('d.m.Y') . ' - ' . $upd->service_period_end->format('d.m.Y');
        }

        return [
            'upd' => [
                'number' => $upd->number,
                'date' => $upd->issue_date ? $upd->issue_date->format('d.m.Y') : '',
                'contract_number' => $upd->contract_number,
                'contract_date' => $upd->contract_date ? $upd->contract_date->format('d.m.Y') : '',
                'shipment_date' => $upd->service_period_start ? $upd->service_period_start->format('d.m.Y') : '',
                'total_without_vat' => $upd->amount,
                'total_vat' => $upd->tax_amount,
                'total_with_vat' => $upd->total_amount,
                'period' => $periodString,
            ],
            'platform' => [
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
            'lessee' => [
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
            'upd_number' => $upd->number,
            'upd_date' => $upd->issue_date ? $upd->issue_date->format('d.m.Y') : '',
            'contract_number' => $upd->contract_number,
            'contract_date' => $upd->contract_date ? $upd->contract_date->format('d.m.Y') : '',
            'shipment_date' => $upd->service_period_start ? $upd->service_period_start->format('d.m.Y') : '',
            'total_without_vat' => $upd->amount,
            'total_vat' => $upd->tax_amount,
            'total_with_vat' => $upd->total_amount,
            'platform_name' => $platformCompany->legal_name,
            'platform_inn' => $platformCompany->inn,
            'platform_kpp' => $platformCompany->kpp,
            'platform_inn_kpp' => $platformCompany->inn . ' / ' . $platformCompany->kpp,
            'platform_address' => $platformCompany->legal_address,
            'lessee_name' => $lesseeCompany->legal_name,
            'lessee_legal_name' => $lesseeCompany->legal_name,
            'lessee_inn' => $lesseeCompany->inn,
            'lessee_kpp' => $lesseeCompany->kpp,
            'lessee_inn_kpp' => $lesseeCompany->inn . ' / ' . $lesseeCompany->kpp,
            'lessee_address' => $lesseeCompany->legal_address,
            'period' => $periodString,
            'items' => $upd->items->map(function($item) {
                return [
                    'code' => $item->code,
                    'name' => $item->name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'amount' => $item->amount,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $item->vat_amount,
                    'total_with_vat' => $item->amount + $item->vat_amount,
                ];
            })->toArray()
        ];
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
            'invoice_items' => $invoice->items->map(function($item) {
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'price' => $item->price,
                    'amount' => $item->amount,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $item->vat_amount,
                ];
            })->toArray()
        ];
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
