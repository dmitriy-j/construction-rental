<?php
// app/Models/CarrierRating.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarrierRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'rating',
        'comment',
        'order_id'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    // Отношения
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
