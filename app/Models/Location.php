<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'company_id'
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
}
