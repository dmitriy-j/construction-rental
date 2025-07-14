<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'number', 'description', 'payment_type',
        'documentation_deadline', 'payment_deadline',
        'penalty_rate', 'file_path'
    ];

    protected $casts = [
    'documentation_deadline' => 'integer', // Приведение к целому числу
    'payment_deadline' => 'integer',       // Приведение к целому числу
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function rentalCondition()
    {
        return $this->hasOne(RentalCondition::class);
    }

    // Создаем условия аренды при создании договора
    /*public static function boot()
    {
        parent::boot();

        static::created(function ($contract) {
            $contract->rentalCondition()->create(
                RentalCondition::defaultForCompany($contract->lesseeCompany)->toArray()
            );
        });
    }*/
}
