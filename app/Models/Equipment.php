<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Support\Carbon;



class Equipment extends Model
{

    use HasFactory;

    protected $fillable = [
    'title', 'slug', 'description', 'company_id', 'category_id',
    'location_id', 'brand', 'model', 'year', 'hours_worked',
    'rating', 'is_featured', 'is_approved'
    ];


    protected $casts = [
        'specifications' => 'array',
        'working_hours_per_day' => 'integer', // Добавить новое поле
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function specifications()
    {
        return $this->hasMany(Specification::class);
    }

    public function rentalTerms()
    {
        return $this->hasMany(EquipmentRentalTerm::class);
    }

    public function images()
    {
        return $this->hasMany(EquipmentImage::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getMainImageAttribute()
    {
        return $this->images()->where('is_main', true)->first()
            ?? $this->images()->first();
    }
    public function availabilities()
    {
        return $this->hasMany(EquipmentAvailability::class);
    }

    public function hasActiveRentalTerms(): bool
    {
        return $this->rentalTerms()->exists();
    }

    public function availability()
    {
        return $this->hasMany(EquipmentAvailability::class);
    }

    public function getAvailabilityStatusAttribute(): string
    {
        // Проверяем только сегодняшний день
        $today = now()->format('Y-m-d');

        $activeToday = EquipmentAvailability::where('equipment_id', $this->id)
            ->where('date', $today)
            ->where(function($query) {
                $query->where('status', 'booked')
                    ->orWhere('status', 'maintenance')
                    ->orWhere(function($q) {
                        $q->where('status', 'temp_reserve')
                            ->where('expires_at', '>', now());
                    });
            })
            ->exists();

        return $activeToday ? 'unavailable' : 'available';
    }

    public function getCurrentStatusAttribute(): string
    {
        return app(EquipmentAvailabilityService::class)->getCurrentStatus($this->id);
    }

    public function getStatusDetailsAttribute(): array
    {
        return app(EquipmentAvailabilityService::class)->getStatusDetails($this);
    }



    // [MODIFIED] Добавлен аксессор даты доступности
    public function getNextAvailableDateAttribute(): ?Carbon
    {
        return app(EquipmentAvailabilityService::class)
            ->calculateNextAvailableDate($this->id);
    }

    public function getMinPriceAttribute()
    {
        return $this->rentalTerms->min('price');
    }

    public function getFutureAvailabilityAttribute()
    {
        return EquipmentAvailability::where('equipment_id', $this->id)
            ->where('date', '>', now()->format('Y-m-d'))
            ->orderBy('date')
            ->get();
    }

    public function isAvailableForPeriod($startDate, $endDate): bool
    {
        return app(EquipmentAvailabilityService::class)->isAvailable(
            $this,
            $startDate,
            $endDate
        );
    }

    protected static function booted()
    {
        static::addGlobalScope('withSpecifications', function ($query) {
            $query->with('specifications');
        });
    }
    public function getNumericSpecValue(string $key): float
    {
        $spec = $this->specifications->firstWhere('key', $key);

        if (!$spec) {
            return 0;
        }

        // Возвращаем числовое значение если оно есть
        if ($key === 'Вес' && $spec->weight) {
            return $spec->weight;
        }

        if ($key === 'Длина' && $spec->length) {
            return $spec->length;
        }

        if ($key === 'Ширина' && $spec->width) {
            return $spec->width;
        }

        if ($key === 'Высота' && $spec->height) {
            return $spec->height;
        }

        // Пытаемся извлечь число из строки
        preg_match('/(\d+[\.,]?\d*)/', $spec->value, $matches);
        return isset($matches[1]) ? (float)str_replace(',', '.', $matches[1]) : 0;
    }
}
