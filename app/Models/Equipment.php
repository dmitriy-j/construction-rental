<?php

namespace App\Models;

use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon; // Добавлен импорт сервиса

class Equipment extends Model
{
    use HasFactory;

    protected $with = ['specifications']; // Автоматическая загрузка

    protected $fillable = [
        'title', 'slug', 'description', 'company_id', 'category_id',
        'location_id', 'brand', 'model', 'year', 'hours_worked',
        'rating', 'is_featured', 'is_approved', 'views', 'equipment_import_id'
    ];

    protected $casts = [
        'specifications' => 'array',
        'working_hours_per_day' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->withDefault([
            'legal_name' => 'Компания недоступна',
        ]);
    }

    public function equipmentImport()
    {
        return $this->belongsTo(EquipmentImport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function requestResponses()
    {
        return $this->hasMany(RentalRequestResponse::class);
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
        $today = now()->format('Y-m-d');

        $activeToday = EquipmentAvailability::where('equipment_id', $this->id)
            ->where('date', $today)
            ->where(function ($query) {
                $query->where('status', 'booked')
                    ->orWhere('status', 'maintenance')
                    ->orWhere(function ($q) {
                        $q->where('status', 'temp_reserve')
                            ->where('expires_at', '>', now());
                    });
            })
            ->exists();

        return $activeToday ? 'unavailable' : 'available';
    }

    public function getCurrentStatusAttribute(): string
    {
        return $this->availabilities()
            ->where('date', now()->format('Y-m-d'))
            ->value('status') ?? EquipmentAvailability::STATUS_AVAILABLE;
    }

    public function getStatusDetailsAttribute(): array
    {
        return app(EquipmentAvailabilityService::class)->getStatusDetails($this);
    }

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
        // Получаем спецификации независимо от состояния загрузки
        $specifications = $this->specifications ?? $this->specifications()->get();

        // Если спецификаций нет вообще
        if ($specifications === null || $specifications->isEmpty()) {
            return 0;
        }

        // Находим нужную спецификацию
        $spec = $specifications->firstWhere('key', $key);

        if (! $spec) {
            return 0;
        }

        // Для веса и габаритов используем специальные поля
        $value = match ($key) {
            'weight' => $spec->weight,
            'length' => $spec->length,
            'width' => $spec->width,
            'height' => $spec->height,
            default => $spec->value
        };

        // Добавим логирование для отладки
        \Log::debug('Equipment specification value', [
            'equipment_id' => $this->id,
            'key' => $key,
            'spec_id' => $spec->id,
            'value' => $value,
        ]);

        return (float) $value;
    }

    public function getDisplayPriceAttribute()
    {
        if (! $this->rentalTerms->isEmpty()) {
            $term = $this->rentalTerms->first();
            $price = $term->price_per_hour;

            // ДОБАВЛЕНО: безопасная проверка пользователя
            $user = auth()->user();
            $company = $user && $user->company ? $user->company : null;

            // Добавляем наценку платформы только если пользователь авторизован и имеет компанию
            if ($company) {
                $pricingService = app(PricingService::class);
                $markup = $pricingService->getPlatformMarkup(
                    $this,
                    $company, // ← используем безопасную переменную
                    1 // 1 час для расчета
                );
                $priceWithMarkup = $price + $pricingService->applyMarkup($price, $markup);
            } else {
                $priceWithMarkup = $price;
            }

            return number_format($priceWithMarkup, 2).' ₽/час';
        }

        return 'Цена не указана';
    }

    public function mainImage()
    {
        return $this->hasOne(EquipmentImage::class)->where('is_main', true)->withDefault();
    }

    public function availabilityStatus()
    {
        return $this->hasOne(EquipmentAvailability::class, 'equipment_id')
            ->where('order_id', request()->route('order')->id)
            ->latest();
    }

    public function getDeliveryStatusAttribute()
    {
        $delivery = EquipmentAvailability::where('equipment_id', $this->id)
            ->where('status', EquipmentAvailability::STATUS_DELIVERY)
            ->where('expires_at', '>', now())
            ->first();

        if ($delivery) {
            return 'В пути (до '.$delivery->expires_at->format('d.m.Y').')';
        }

        return null;
    }

    public function getDimensionsAttribute(): string
    {
        return sprintf('%.2f × %.2f × %.2f м',
            $this->getNumericSpecValue('length'),
            $this->getNumericSpecValue('width'),
            $this->getNumericSpecValue('height')
        );
    }

    public function activeOperator()
    {
        return $this->belongsTo(Operator::class, 'operator_id')
            ->where('is_active', true);
    }

    public function getActiveOperatorAttribute()
    {
        return $this->activeOperator()->first();
    }

    public function operators(): HasMany
    {
        return $this->hasMany(Operator::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function activeOperators()
    {
        return $this->hasMany(Operator::class)->where('is_active', true);
    }

    public function hasActiveOperator(string $shiftType): bool
    {
        return $this->operators()
            ->where('shift_type', $shiftType)
            ->where('is_active', true)
            ->exists();
    }

    public function dayOperators()
    {
        return $this->hasMany(Operator::class)->where('shift_type', 'day');
    }

    public function nightOperators()
    {
        return $this->hasMany(Operator::class)->where('shift_type', 'night');
    }

    public function hasDayOperator()
    {
        return $this->operators()
            ->where('shift_type', Operator::SHIFT_DAY)
            ->where('is_active', true)
            ->exists();
    }

    public function hasNightOperator()
    {
        return $this->operators()
            ->where('shift_type', Operator::SHIFT_NIGHT)
            ->where('is_active', true)
            ->exists();
    }

    public function hasActiveDayOperator(): bool
    {
        return $this->operators()
            ->where('shift_type', Operator::SHIFT_DAY)
            ->where('is_active', true)
            ->exists();
    }

    public function hasActiveNightOperator(): bool
    {
        return $this->operators()
            ->where('shift_type', Operator::SHIFT_NIGHT)
            ->where('is_active', true)
            ->exists();
    }
}
