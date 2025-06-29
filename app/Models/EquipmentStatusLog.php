<?php

namespace App\Models;> $company = Company::find(first())

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentStatusLog extends Model
{
    protected $fillable = [
        'equipment_id',
        'order_id',
        'status',
        'notes',
        'start_time',
        'end_time',
        'customer_responsible',
        'penalty_amount'
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
