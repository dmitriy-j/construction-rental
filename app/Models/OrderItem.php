<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    // Константы статусов
    const STATUS_PENDING = 'pending';

    const STATUS_IN_DELIVERY = 'in_delivery';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'equipment_id',
        'rental_term_id',
        'rental_condition_id', // Добавлено
        'quantity',
        'base_price',
        'price_per_unit',
        'platform_fee',
        'discount_amount',
        'delivery_cost',
        'total_price',
        'period_count',
        'delivery_from_id',
        'delivery_to_id',
        'lessor_company_id',
        'distance_km',
        'status', // Добавляем новое поле
        'fixed_lessor_price', // Добавляем
        'fixed_customer_price', // Добавляем
        'proposal_id',

    ];

    protected $casts = [
        'delivery_cost' => 'float',
        'status' => 'string',

    ];

    protected $attributes = [
        'distance_km' => 0, // Значение по умолчанию
    ];

    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class)->withDefault([
            'title' => 'Удаленное оборудование',
            'brand' => 'N/A',
            'model' => 'N/A',
        ]);
    }

    public function proposal()
    {
        return $this->belongsTo(RentalRequestResponse::class, 'proposal_id');
    }

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(EquipmentRentalTerm::class, 'rental_term_id');
    }

    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class, 'rental_condition_id');
    }

    public function deliveryNote()
    {
        return $this->hasOne(DeliveryNote::class, 'order_item_id');
    }

    protected static function booted()
    {
        static::updating(function ($model) {
            $original = $model->getOriginal();

            // Запрещаем изменение критических полей после создания
            $protected = [
                'base_price',
                'price_per_unit',
                'rental_term_id',
                'rental_condition_id',
                'quantity',
            ];

            foreach ($protected as $field) {
                if ($original[$field] != $model->$field) {
                    throw new \Exception("Cannot change $field after creation");
                }
            }
        });
    }

    public function deliveryFrom(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_from_id');
    }

    public function deliveryTo(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_to_id');
    }

    public function lessorCompany()
    {
        return $this->belongsTo(Company::class, 'lessor_company_id');
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_IN_DELIVERY => 'В пути',
            self::STATUS_ACTIVE => 'Активна',
            self::STATUS_COMPLETED => 'Завершена',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_DELIVERY => 'info',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'secondary',
            default => 'light',
        };
    }

    public function getPricePerUnitAttribute($value)
    {
        return $this->fixed_customer_price ?? $value;
    }

    public function getBasePriceAttribute($value)
    {
        return $this->fixed_customer_price ?? $value;
    }

    public function getLessorPriceAttribute()
    {
        return $this->fixed_lessor_price ?? $this->rentalTerm->price_per_hour;
    }
}
