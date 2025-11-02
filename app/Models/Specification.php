<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Specification extends Model
{
    use HasFactory;

    protected $table = 'equipment_specifications';

    protected $fillable = [
        'equipment_id',
        'key',
        'value',
        'weight',
        'length',
        'width',
        'height',
    ];

    // ⚠️ ИСПРАВЛЕНИЕ: Добавлено приведение типов для дробных чисел
    protected $casts = [
        'value' => 'float',
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
    ];

    public function equipment()
    {
        \Log::debug('Full specification', [
            'spec' => $this->log, // используем наш аксессор
        ]);

        return $this->belongsTo(Equipment::class);
    }

    public function getLogAttribute()
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    // ⚠️ ИСПРАВЛЕНИЕ: Аксессор для безопасного получения числовых значений
    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : (float) $value,
            set: fn ($value) => $value === null ? null : (is_numeric($value) ? (float) $value : $value)
        );
    }
}
