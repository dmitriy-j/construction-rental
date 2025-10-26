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
        'company_id',
        'lessor_company_id', // Добавляем новое поле
        'lessee_company_id', // Добавляем новое поле
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

    // Компания, создавшая договор (возможно, платформа)
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Арендодатель
    public function lessorCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessor_company_id');
    }

    // Арендатор
    public function lesseeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessee_company_id');
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
