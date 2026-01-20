<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'mapping', // Это поле должно быть здесь
        'is_active',
        'file_example_path',
        'validation_rules',
        'upd_specific_settings',
    ];

    protected $casts = [
        'mapping' => 'array', // Это должно быть
        'is_active' => 'boolean',
        'validation_rules' => 'array',
        'upd_specific_settings' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope для шаблонов УПД
     */
    public function scopeUpdTemplates($query)
    {
        return $query->where('type', 'upd');
    }

    /**
     * Scope для активных шаблонов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getUpdSettings(): array
    {
        return $this->upd_specific_settings ?? [
            'vat_rates' => [
                '22%' => 22,
                '10%' => 10,
                '0%' => 0,
                'без ндс' => 0,
            ],
            'default_vat_rate' => 20,
            'required_fields' => ['number', 'issue_date', 'amount', 'total_amount'],
            'date_format' => 'd.m.Y',
            'number_patterns' => ['УПД', 'Счет-фактура', 'СФ'],
        ];
    }

    public static function getDefaultUpdMappingConfig(): array
    {
        return [
            'header' => [
                'number' => ['cell' => 'B2', 'description' => 'Номер УПД'],
                'issue_date' => ['cell' => 'B3', 'description' => 'Дата УПД'],
                'seller' => [
                    'name' => ['cell' => 'B4', 'description' => 'Наименование продавца'],
                    'inn' => ['cell' => 'B5', 'description' => 'ИНН продавца'],
                    'kpp' => ['cell' => 'B6', 'description' => 'КПП продавца'],
                ],
                'buyer' => [
                    'name' => ['cell' => 'B7', 'description' => 'Наименование покупателя'],
                    'inn' => ['cell' => 'B8', 'description' => 'ИНН покупателя'],
                    'kpp' => ['cell' => 'B9', 'description' => 'КПП покупателя'],
                ],
            ],
            'amounts' => [
                'without_vat' => ['cell' => 'B10', 'description' => 'Сумма без НДС'],
                'vat' => ['cell' => 'B11', 'description' => 'Сумма НДС'],
                'total' => ['cell' => 'B12', 'description' => 'Всего с НДС'],
            ],
            'items' => [
                'start_row' => 15,
                'columns' => [
                    'name' => ['cell' => 'A', 'description' => 'Наименование товара/услуги'],
                    'quantity' => ['cell' => 'B', 'description' => 'Количество'],
                    'unit' => ['cell' => 'C', 'description' => 'Единица измерения'],
                    'price' => ['cell' => 'D', 'description' => 'Цена'],
                    'amount' => ['cell' => 'E', 'description' => 'Сумма'],
                    'vat_rate' => ['cell' => 'F', 'description' => 'Ставка НДС'],
                    'vat_amount' => ['cell' => 'G', 'description' => 'Сумма НДС'],
                ],
            ],
        ];
    }
}
