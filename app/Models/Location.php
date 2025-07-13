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
        $parts = explode(',', $this->address);
        return count($parts) > 2
            ? implode(',', [$parts[0], $parts[1]])
            : $this->address;
    }
}
