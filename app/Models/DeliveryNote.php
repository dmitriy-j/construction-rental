<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id', 'delivery_date', 'driver_name',
        'receiver_name', 'receiver_signature_path',

    ];

    protected $casts = [
        'delivery_date' => 'date' // Добавьте это
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
