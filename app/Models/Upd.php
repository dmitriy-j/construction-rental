<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Upd extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'lessor_company_id',
        'lessee_company_id',
        'number',
        'issue_date',
        'service_period_start',
        'service_period_end',
        'amount',
        'tax_amount',
        'total_amount',
        'tax_system',
        'contract_number',
        'contract_date',
        'invoice_number',
        'invoice_date',
        'status',
        'rejection_reason',
        'file_path',
        'idempotency_key',
        'accepted_at',
        'rejected_at',
        'parsed_data',

        // Новые поля для 1С
        '1c_guid',
        '1c_number',
        '1c_date',
        'operation_type',
        'document_type',
        'payment_conditions',
        'currency',
        'currency_rate',
        'vat_included',
        'lessor_sign_position',
        'lessor_sign_name',
        'lessor_sign_date',
        'lessee_sign_position',
        'lessee_sign_name',
        'lessee_sign_date',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'contract_date' => 'date',
        'invoice_date' => 'date',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'parsed_data' => 'array',

        // Приведение типов для новых полей
        '1c_date' => 'date',
        'currency_rate' => 'decimal:4',
        'vat_included' => 'boolean',
        'lessor_sign_date' => 'datetime',
        'lessee_sign_date' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lessorCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessor_company_id');
    }

    public function lesseeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessee_company_id');
    }

    public function accept(): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('УПД уже был обработан.');
        }

        DB::beginTransaction();

        try {
            $this->status = self::STATUS_ACCEPTED;
            $this->accepted_at = now();
            $this->save();

            // Используем BalanceService для создания проводки
            app(\App\Services\BalanceService::class)->commitTransaction(
                $this->lessorCompany,
                $this->total_amount,
                'credit',
                TransactionEntry::PURPOSE_UPD_DEBT,
                $this,
                "Принят УПД №{$this->number} от {$this->issue_date->format('d.m.Y')}",
                'upd_accept_' . $this->id
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка принятия УПД', ['upd_id' => $this->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function reject(string $reason): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('УПД уже был обработан.');
        }

        $this->status = self::STATUS_REJECTED;
        $this->rejection_reason = $reason;
        $this->rejected_at = now();
        $this->save();
    }

    /**
     * Генерация данных для экспорта в 1С
     */
    public function to1CFormat(): array
    {
        return [
            'document' => [
                'guid' => $this->{'1c_guid'} ?? Str::uuid(),
                'number' => $this->number,
                'date' => $this->issue_date->format('Y-m-d'),
                'operation_type' => $this->operation_type ?? 'Услуги',
                'document_type' => $this->document_type ?? 'УПД',
                'payment_conditions' => $this->payment_conditions,
                'currency' => $this->currency ?? 'RUB',
                'currency_rate' => $this->currency_rate ?? 1,
                'vat_included' => $this->vat_included ?? true,
            ],
            'seller' => array_merge($this->lessorCompany->get1CData(), [
                'signature' => [
                    'position' => $this->lessor_sign_position,
                    'name' => $this->lessor_sign_name,
                    'date' => $this->lessor_sign_date?->format('Y-m-d'),
                ]
            ]),
            'buyer' => array_merge($this->lesseeCompany->get1CData(), [
                'signature' => [
                    'position' => $this->lessee_sign_position,
                    'name' => $this->lessee_sign_name,
                    'date' => $this->lessee_sign_date?->format('Y-m-d'),
                ]
            ]),
            'contract' => [
                'number' => $this->contract_number,
                'date' => $this->contract_date?->format('Y-m-d'),
            ],
            'invoice' => [
                'number' => $this->invoice_number,
                'date' => $this->invoice_date?->format('Y-m-d'),
            ],
            'amounts' => [
                'without_tax' => (float) $this->amount,
                'tax' => (float) $this->tax_amount,
                'total' => (float) $this->total_amount,
            ],
            'tax_system' => $this->tax_system,
            'items' => $this->getItemsData(),
            'additional_fields' => $this->getAdditional1CFields(),
        ];
    }

    protected function getAdditional1CFields(): array
    {
        return [
            'КодОперации' => 'УслугиАренды',
            'Основание' => $this->contract_number ?
                "Договор №{$this->contract_number} от {$this->contract_date->format('d.m.Y')}" :
                "Заказ №{$this->order_id}",
            'ВидНоменклатуры' => 'Услуги',
            'Склад' => 'Основной',
            'Подразделение' => 'Основное',
        ];
    }

    /**
     * Получение данных позиций для экспорта в 1С
     */
    protected function getItemsData(): array
    {
        return $this->order->items->map(function ($item) {
            return [
                'name' => $item->equipment->title,
                'quantity' => $item->quantity,
                'unit' => 'шт.',
                'price' => (float) $item->price_per_unit,
                'amount' => (float) $item->total_price,
                'vat_rate' => $this->getVatRate(),
                'vat_amount' => (float) $this->calculateVatAmount($item->total_price),
                'accounting_info' => $this->getAccountingInfo(),
            ];
        })->toArray();
    }

    public function waybill()
    {
        return $this->hasOne(Waybill::class);
    }

    public function completionAct()
    {
        return $this->hasOneThrough(
            CompletionAct::class,
            Waybill::class,
            'upd_id', // Внешний ключ в таблице waybills
            'waybill_id', // Внешний ключ в таблице completion_acts
            'id', // Локальный ключ в таблице upds
            'id' // Локальный ключ в таблице waybills
        );
    }

    protected function getVatRate(): string
    {
        return $this->tax_system === 'osn' ? '20%' : 'Без НДС';
    }

    protected function calculateVatAmount(float $amount): float
    {
        return $this->tax_system === 'osn' ? $amount * 0.2 : 0;
    }

    protected function getAccountingInfo(): array
    {
        // Коды счетов учета в соответствии с планом счетов РФ
        return [
            'income_account' => '90.01', // Выручка от реализации
            'expense_account' => '90.02', // Себестоимость реализации
            'vat_account' => '90.03', // НДС
        ];
    }

    public function items()
    {
        return $this->hasMany(UpdItem::class);
    }
}
