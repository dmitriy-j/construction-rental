<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalRequestResponse extends Model
{
    protected $fillable = [
        'rental_request_id', 'lessor_id', 'equipment_id', 'proposed_price',
        'message', 'availability_dates', 'additional_terms', 'status', 'counter_price', 'expires_at'
    ];

    protected $casts = [
        'availability_dates' => 'array',
        'proposed_price' => 'decimal:2',
        'counter_price' => 'decimal:2',
        'expires_at' => 'datetime'
    ];

    // Отношения
    public function rentalRequest() { return $this->belongsTo(RentalRequest::class); }
    public function lessor() { return $this->belongsTo(User::class, 'lessor_id'); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function order() { return $this->hasOne(Order::class, 'request_response_id'); }

    // Scopes
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeAccepted($query) { return $query->where('status', 'accepted'); }
}
