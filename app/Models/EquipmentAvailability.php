<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentAvailability extends Model
{

    protected $table = 'equipment_availability';
    protected $fillable = [
        'equipment_id',
        'date',
        'status',
        'order_id',
        'expires_at'
    ];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime'
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
