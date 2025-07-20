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
        'verified_at'
    ];

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
                    ->whereHas('contract', function($query) {
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
            'phone' => $phone
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
}
