<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Waybill extends Model
{
    protected $fillable = [
        'order_id', 'equipment_id', 'operator_id', 'work_date',
        'hours_worked', 'downtime_hours', 'downtime_cause',
        'operator_signature_path', 'customer_signature_path', 'notes'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
