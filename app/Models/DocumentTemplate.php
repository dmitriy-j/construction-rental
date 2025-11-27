<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasFactory;

    // Константы типов документов
    const TYPE_UPD = 'upd';
    const TYPE_INVOICE = 'invoice';
    const TYPE_ACT = 'act';
    const TYPE_CONTRACT = 'contract';
    const TYPE_WAYBILL = 'waybill';
    const TYPE_COMPLETION_ACT = 'completion_act';
    const TYPE_DELIVERY_NOTE = 'delivery_note';

    // Сценарии использования для всех документов
    const SCENARIO_UPD_LESSEE = 'upd_lessee';
    const SCENARIO_UPD_LESSOR = 'upd_lessor';
    const SCENARIO_INVOICE_ORDER = 'invoice_order';
    const SCENARIO_INVOICE_UPD = 'invoice_upd';
    const SCENARIO_INVOICE_ADVANCE = 'invoice_advance';
    const SCENARIO_COMPLETION_ACT = 'completion_act';

    // Специфичные сценарии для счетов
    const INVOICE_SCENARIO_ADVANCE_ORDER = 'advance_order';
    const INVOICE_SCENARIO_ADVANCE_UPD = 'advance_upd';
    const INVOICE_SCENARIO_POSTPAYMENT_UPD = 'postpayment_upd';
    const INVOICE_SCENARIO_CORRECTION = 'correction';
    const INVOICE_SCENARIO_PARTIAL_PAYMENT = 'partial_payment';

    // Статусы счетов
    const INVOICE_STATUS_DRAFT = 'draft';
    const INVOICE_STATUS_ISSUED = 'issued';
    const INVOICE_STATUS_PAID = 'paid';
    const INVOICE_STATUS_OVERDUE = 'overdue';
    const INVOICE_STATUS_CANCELLED = 'cancelled';

    // Указываем, какие поля могут быть массово присвоены
    protected $fillable = [
        'name',
        'type',
        'scenario',
        'description',
        'file_path',
        'mapping',
        'is_active',
    ];

    // Указываем, что поля должны быть преобразованы в соответствующие типы
    protected $casts = [
        'mapping' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Получить доступные типы документов
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_CONTRACT => 'Договор',
            self::TYPE_INVOICE => 'Счет',
            self::TYPE_ACT => 'Акт выполненных работ',
            self::TYPE_WAYBILL => 'Путевой лист',
            self::TYPE_COMPLETION_ACT => 'Акт сдачи-приемки',
            self::TYPE_UPD => 'УПД',
            self::TYPE_DELIVERY_NOTE => 'Накладная',
        ];
    }

    /**
     * Получить доступные сценарии
     */
    public static function getScenarios(): array
    {
        return [
            self::SCENARIO_UPD_LESSEE => 'УПД для арендатора',
            self::SCENARIO_UPD_LESSOR => 'УПД для арендодателя',
            self::SCENARIO_INVOICE_ORDER => 'Счет к заказу',
            self::SCENARIO_INVOICE_UPD => 'Счет к УПД',
            self::SCENARIO_INVOICE_ADVANCE => 'Авансовый счет',
            self::SCENARIO_COMPLETION_ACT => 'Акт выполненных работ',
        ];
    }

   /**
     * Получить сценарии для счетов
     */
    public static function getInvoiceScenarios(): array
    {
        return [
            self::INVOICE_SCENARIO_ADVANCE_ORDER => 'Предоплатный счет к заказу',
            self::INVOICE_SCENARIO_POSTPAYMENT_UPD => 'Постоплатный счет к УПД',
            self::INVOICE_SCENARIO_PARTIAL_PAYMENT => 'Счет на частичную оплату',
            self::INVOICE_SCENARIO_CORRECTION => 'Корректировочный счет',
        ];
    }

    /**
     * Получить статусы счетов
     */
    public static function getInvoiceStatuses(): array
    {
        return [
            self::INVOICE_STATUS_DRAFT => 'Черновик',
            self::INVOICE_STATUS_ISSUED => 'Выставлен',
            self::INVOICE_STATUS_PAID => 'Оплачен',
            self::INVOICE_STATUS_OVERDUE => 'Просрочен',
            self::INVOICE_STATUS_CANCELLED => 'Отменен',
        ];
    }

    /**
     * Scope для активных шаблонов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для шаблонов по типу
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope для шаблонов по сценарию
     */
    public function scopeByScenario($query, string $scenario)
    {
        return $query->where('scenario', $scenario);
    }

    /**
     * Проверить, является ли шаблоном счета
     */
    public function isInvoiceTemplate(): bool
    {
        return $this->type === self::TYPE_INVOICE;
    }

    /**
     * Проверить, является ли шаблоном УПД
     */
    public function isUpdTemplate(): bool
    {
        return $this->type === self::TYPE_UPD;
    }
}
