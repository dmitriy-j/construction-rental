<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_lessor',
        'is_lessee',
        'is_carrier',
        'is_platform',
        'legal_name',
        'tax_system',
        'inn',
        'kpp',
        'ogrn',
        'okpo',
        'legal_address',
        'actual_address',
        'bank_name',
        'bank_account',
        'bik',
        'correspondent_account',
        'director_name',
        'phone',
        'contacts',
        'status',
        'rejection_reason',
        'credit_limit',
        'current_debt',
        'verified_at',

        // Новые поля для 1С
        '1c_guid',
        '1c_code',
    ];

    protected $casts = [
        'is_platform' => 'boolean',
        'credit_limit' => 'decimal:2',
        'current_debt' => 'decimal:2',
    ];

    // Добавляем scope для быстрого поиска платформы
    public function scopePlatform($query)
    {
        return $query->where('is_platform', true);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function lesseeOrders()
    {
        return $this->hasMany(Order::class, 'lessee_company_id');
    }

    public function lessorOrders()
    {
        return $this->hasMany(Order::class, 'lessor_company_id');
    }

    public function isCarrier(): bool
    {
        return $this->is_carrier;
    }

    public function isRegistered(): bool
    {
        return ! empty($this->legal_name) && ! empty($this->inn) && $this->status === 'verified';
    }

    public function carrierDeliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'carrier_company_id');
    }

    public function platformMarkups()
    {
        return $this->morphMany(PlatformMarkup::class, 'markupable');
    }

    public function getMarkupAttribute()
    {
        return $this->platformMarkups()->first();
    }

    public function __toString()
    {
        return $this->legal_name;
    }

    public function rentalConditions()
    {
        return $this->hasMany(RentalCondition::class);
    }

    public function defaultRentalCondition()
    {
        return $this->rentalConditions()->where('is_default', true)->first();
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function activeRentalConditions()
    {
        return $this->hasMany(RentalCondition::class)
            ->whereHas('contract', function ($query) {
                $query->where('is_active', true);
            });
    }

    public function getContactInfo(): array
    {
        $name = $this->director_name;
        $phone = $this->phone;

        // Пытаемся извлечь контактные данные из поля contacts
        if (preg_match('/([^:]+):\s*(\+\d[\d\s\-\(\)]+)/', $this->contacts, $matches)) {
            $name = trim($matches[1]);
            $phone = trim($matches[2]);
        }

        return [
            'name' => $name,
            'phone' => $phone,
        ];
    }

    public function carrierRatings()
    {
        return $this->hasMany(CarrierRating::class);
    }

    public function getAverageRatingAttribute()
    {
        return $this->carrierRatings()->avg('rating') ?? 0;
    }

    public function activeContract()
    {
        return $this->contracts()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function get1CData(): array
    {
        return [
            'name' => $this->legal_name,
            'inn' => $this->inn,
            'kpp' => $this->kpp,
            'ogrn' => $this->ogrn,
            'okpo' => $this->okpo,
            'address' => $this->legal_address,
            'bank_account' => $this->bank_account,
            'bank_name' => $this->bank_name,
            'bik' => $this->bik,
            'correspondent_account' => $this->correspondent_account,
            'manager_name' => $this->director_name,
            'phone' => $this->phone,
            'email' => $this->email,
            '1c_guid' => $this->{'1c_guid'}, // GUID контрагента в 1С
            '1c_code' => $this->{'1c_code'}, // Код контрагента в 1С
        ];
    }

    public function getTaxSystemCode(): string
    {
        return match ($this->tax_system) {
            'osn' => 'ОСН',
            'usn' => 'УСН',
            'usn_income_minus_expenses' => 'УСН Доходы-Расходы',
            'envd' => 'ЕНВД',
            'patent' => 'Патент',
            default => 'ОСН',
        };
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }
}
