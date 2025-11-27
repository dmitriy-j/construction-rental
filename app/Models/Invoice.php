<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'upd_id', // Добавляем связь с УПД
        'number',
        'issue_date',
        'due_date',
        'amount',
        'amount_paid',
        'platform_fee',
        'status',
        //'file_path',
        'idempotency_key',
        'paid_at',
        'cancellation_reason', // Добавляем поле для причины отмены
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Константы статусов (используем существующие из миграции)
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELED = 'canceled';

    /**
     * Заказ, для которого выписан счет
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Генерация упрощенного русского номера счета
     */
    public static function generateSimpleInvoiceNumber(): string
    {
        $currentYear = date('Y');
        $lastInvoice = self::whereYear('created_at', $currentYear)
                          ->orderBy('id', 'desc')
                          ->first();

        $sequenceNumber = $lastInvoice ? (intval(substr($lastInvoice->number, -4)) + 1) : 1;

        return 'СЧ/' . $currentYear . '/' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Автоматическая генерация номера при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->number)) {
                $invoice->number = self::generateSimpleInvoiceNumber();
            }
        });
    }

    /**
     * Компания-плательщик
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Связь с УПД (если счет связан с УПД)
     */
    public function upd()
    {
        return $this->belongsTo(Upd::class);
    }

    /**
     * Генерация файла счета на лету
     */
    public function generateFile(): string
    {
        try {
            $templateService = app(\App\Services\DocumentGeneratorService::class);
            $dataService = app(\App\Services\InvoiceGeneratorService::class);

            // Получаем данные для счета
            $invoiceData = $dataService->prepareInvoiceDataForDownload($this);

            // Находим шаблон по сценарию
            $scenario = $this->upd_id ?
                \App\Models\DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD :
                \App\Models\DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER;

            Log::debug('Поиск шаблона счета', [
                'invoice_id' => $this->id,
                'upd_id' => $this->upd_id,
                'scenario' => $scenario,
                'scenario_constants' => [
                    'postpayment_upd' => \App\Models\DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD,
                    'advance_order' => \App\Models\DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER,
                ]
            ]);

            $template = \App\Models\DocumentTemplate::active()
                ->byType(\App\Models\DocumentTemplate::TYPE_INVOICE)
                ->byScenario($scenario)
                ->first();

            if (!$template) {
                // Логируем доступные шаблоны для отладки
                $availableTemplates = \App\Models\DocumentTemplate::active()
                    ->byType(\App\Models\DocumentTemplate::TYPE_INVOICE)
                    ->get();

                Log::warning('Шаблон не найден по сценарию, проверяем доступные шаблоны', [
                    'scenario' => $scenario,
                    'available_templates' => $availableTemplates->pluck('id', 'scenario'),
                    'template_count' => $availableTemplates->count()
                ]);

                // Если не нашли по сценарию, ищем любой активный шаблон счета
                $template = \App\Models\DocumentTemplate::active()
                    ->byType(\App\Models\DocumentTemplate::TYPE_INVOICE)
                    ->first();

                if (!$template) {
                    throw new \Exception("Активный шаблон счета не найден в системе");
                }
            }

            Log::info('Найден шаблон для генерации счета', [
                'invoice_id' => $this->id,
                'template_id' => $template->id,
                'template_name' => $template->name,
                'scenario' => $scenario
            ]);

            // Генерируем файл в памяти
            return $templateService->generateDocumentInMemory($template, $invoiceData);

        } catch (\Exception $e) {
            Log::error('Ошибка генерации файла счета', [
                'invoice_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Позиции счета
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Получить статус на русском языке
     */
    public function getStatusText(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_SENT => 'Отправлен',
            self::STATUS_VIEWED => 'Просмотрен',
            self::STATUS_PAID => 'Оплачен',
            self::STATUS_OVERDUE => 'Просрочен',
            self::STATUS_CANCELED => 'Отменен',
            default => $this->status,
        };
    }

    /**
     * Получить цвет статуса для отображения
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SENT => 'info',
            self::STATUS_VIEWED => 'primary',
            self::STATUS_PAID => 'success',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_CANCELED => 'dark',
            default => 'light',
        };
    }

    /**
     * Рассчитать оставшуюся сумму к оплате
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount - $this->amount_paid
        );
    }

    /**
     * Проверить, оплачен ли счет полностью
     */
    protected function isFullyPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->remaining_amount <= 0
        );
    }

    /**
     * Проверить, просрочен ли счет
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => !$this->is_fully_paid && $this->due_date->isPast()
        );
    }

    /**
     * Получить прогресс оплаты в процентах
     */
    protected function paymentProgress(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount > 0 ? round(($this->amount_paid / $this->amount) * 100, 2) : 0
        );
    }

    /**
     * Обновить статус счета на основе оплат и дат
     */
    public function updateStatus(): void
    {
        $newStatus = $this->status;

        if ($this->is_fully_paid) {
            $newStatus = self::STATUS_PAID;
            $this->paid_at = $this->paid_at ?: now();
        } elseif ($this->is_overdue) {
            $newStatus = self::STATUS_OVERDUE;
        } elseif ($this->status === self::STATUS_DRAFT && $this->file_path) {
            $newStatus = self::STATUS_SENT;
        }

        if ($newStatus !== $this->status) {
            $this->update(['status' => $newStatus]);
        }
    }

    /**
     * Отменить счет
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELED,
            'cancellation_reason' => $reason
        ]);
    }

    /**
     * Добавить платеж
     */
    public function addPayment(float $amount): void
    {
        $this->update([
            'amount_paid' => $this->amount_paid + $amount
        ]);

        $this->updateStatus();
    }

    /**
     * Получить доступные статусы
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_SENT => 'Отправлен',
            self::STATUS_VIEWED => 'Просмотрен',
            self::STATUS_PAID => 'Оплачен',
            self::STATUS_OVERDUE => 'Просрочен',
            self::STATUS_CANCELED => 'Отменен',
        ];
    }
}
