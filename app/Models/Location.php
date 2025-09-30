<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'company_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Новый метод для получения короткого адреса
    public function getShortAddressAttribute(): string
    {
        // Извлекаем только улицу и дом
        preg_match('/(ул\.|улица|проспект|пр\.|шоссе|б-р) [\w\s\.-]+,?\s*\d+/u', $this->address, $matches);

        return $matches[0] ?? $this->address;
    }

    public function scopeWithoutCoordinates($query)
    {
        return $query->whereNull('latitude')->orWhereNull('longitude');
    }

    public function rentalRequests(): HasMany
    {
        return $this->hasMany(RentalRequest::class, 'location_id');
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'location_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

     public function getFullAddressAttribute(): string
    {
        return $this->address; // Упрощаем, так как нет отдельных полей города и региона
    }

    public function getCoordinatesAttribute(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }

        return null;
    }

    public function getIsActiveAttribute(): bool
    {
        return true;
    }
}
