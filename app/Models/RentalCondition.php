<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'company_id',
        'shift_hours',
        'shifts_per_day',
        'transportation',
        'fuel_responsibility',
        'extension_policy',
        'payment_type',
        'fuel_consumption_rate',
        'distance_rate',
        'volume_rate',
        'is_default',
        'delivery_location_id',
        'delivery_cost_per_km',
        'loading_cost',
        'unloading_cost',

    ];

    protected $casts = [
        'is_default' => 'boolean',

    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_location_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
