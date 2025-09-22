<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'equipment_id',
        'full_name',
        'phone',
        'license_number',
        'qualification',
        'is_active',
        'shift_type',
    ];

    // Добавляем константы для типов смен
    const SHIFT_DAY = 'day';

    const SHIFT_NIGHT = 'night';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function getShiftTypeTextAttribute(): string
    {
        return $this->shift_type === self::SHIFT_DAY ? 'Дневная' : 'Ночная';
    }

    public function scopeDayOperators($query)
    {
        return $query->where('shift_type', self::SHIFT_DAY);
    }

    public function scopeNightOperators($query)
    {
        return $query->where('shift_type', self::SHIFT_NIGHT);
    }
}
