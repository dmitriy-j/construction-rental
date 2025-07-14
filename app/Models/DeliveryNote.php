<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNote extends Model
{
    use HasFactory;

    // Константы типов транспорта
    const VEHICLE_25T = 'truck_25t';
    const VEHICLE_45T = 'truck_45t';
    const VEHICLE_110T = 'truck_110t';

    protected $fillable = [
        'order_id',
        'delivery_from_id',
        'delivery_to_id',
        'delivery_date',
        'driver_name',
        'receiver_name',
        'receiver_signature_path',
        'equipment_condition',
        'vehicle_type',
        'distance_km',
        'calculated_cost'
    ];

    protected $casts = [
        'delivery_date' => 'date'
    ];

    public static function vehicleTypes(): array
    {
        return [
            self::VEHICLE_25T => 'До 25 тонн (200 руб/км)',
            self::VEHICLE_45T => 'До 45 тонн (250 руб/км)',
            self::VEHICLE_110T => 'До 110 тонн (350 руб/км)',
        ];
    }

    public function calculateDeliveryCost(): void
    {
        $transportService = app(TransportCalculatorService::class);
        $rates = $transportService->getTransportRates();

        $this->calculated_cost = $this->distance_km * ($rates[$this->vehicle_type] ?? 200);
        $this->save();
    }

    public function setEquipmentDimensions(Equipment $equipment): void
    {
        $this->weight = $equipment->specifications->where('key', 'weight')->first()->value ?? 0;
        $this->length = $equipment->specifications->where('key', 'length')->first()->value ?? 0;
        $this->width = $equipment->specifications->where('key', 'width')->first()->value ?? 0;
        $this->height = $equipment->specifications->where('key', 'height')->first()->value ?? 0;
    }


    public function deliveryFrom(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_from_id');
    }

    public function deliveryTo(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_to_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
