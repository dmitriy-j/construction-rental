<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', // ID компании платформы
        'counterparty_type', // 'lessor' или 'lessee'
        'counterparty_company_id', // ID компании-контрагента
        'number',
        'description',
        'payment_type',
        'documentation_deadline',
        'payment_deadline',
        'penalty_rate',
        'file_path',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'documentation_deadline' => 'integer',
        'payment_deadline' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Компания платформы
    public function platformCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Алиас для обратной совместимости - ОБНОВЛЕНО
    public function company(): BelongsTo
    {
        return $this->platformCompany();
    }

    // Компания-контрагент (арендодатель или арендатор)
    public function counterpartyCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'counterparty_company_id');
    }

    // Алиасы для обратной совместимости - ОБНОВЛЕНО
    public function lessorCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'counterparty_company_id');
    }

    public function lesseeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'counterparty_company_id');
    }

    // Scope для быстрого поиска договоров с арендодателями
    public function scopeWithLessors($query)
    {
        return $query->where('counterparty_type', 'lessor');
    }

    // Scope для быстрого поиска договоров с арендаторами
    public function scopeWithLessees($query)
    {
        return $query->where('counterparty_type', 'lessee');
    }

    // Scope для поиска договоров с конкретной компанией
    public function scopeWithCounterparty($query, $companyId)
    {
        return $query->where('counterparty_company_id', $companyId);
    }

    // Получить тип контрагента в читаемом виде
    public function getCounterpartyTypeLabelAttribute(): string
    {
        return $this->counterparty_type === 'lessor' ? 'Арендодатель' : 'Арендатор';
    }

    // Проверить, является ли контрагент арендодателем
    public function isWithLessor(): bool
    {
        return $this->counterparty_type === 'lessor';
    }

    // Проверить, является ли контрагент арендатором
    public function isWithLessee(): bool
    {
        return $this->counterparty_type === 'lessee';
    }

    // Получить правильное название компании для отображения
    public function getDisplayLessorCompanyAttribute()
    {
        return $this->counterparty_type === 'lessor' ? $this->counterpartyCompany : null;
    }

    public function getDisplayLesseeCompanyAttribute()
    {
        return $this->counterparty_type === 'lessee' ? $this->counterpartyCompany : null;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function rentalConditions(): HasMany
    {
        return $this->hasMany(RentalCondition::class);
    }
}
