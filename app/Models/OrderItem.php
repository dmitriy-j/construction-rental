<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
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
        'total_price',
        'period_count',

    ];

    protected $guarded = ['id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class)->withDefault([
            'title' => 'Удаленное оборудование',
            'brand' => 'N/A',
            'model' => 'N/A'
        ]);
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

    public function getDeliveryCostAttribute(): float
    {
        return $this->deliveryNote->calculated_cost ?? 0;
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
                'quantity'
            ];

            foreach ($protected as $field) {
                if ($model->$field != $original[$field]) {
                    throw new \Exception("Cannot change $field after creation");
                }
            }
        });
    }
}
