<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB; // Добавлено
use Illuminate\Support\Facades\Log; // Добавлено


class DeliveryNote extends Model
{
    use HasFactory;

    // Типы накладных
    const TYPE_LESSOR_TO_PLATFORM = 'lessor_to_platform';
    const TYPE_PLATFORM_TO_LESSEE = 'platform_to_lessee';

    // Типы транспорта
    const VEHICLE_25T = 'truck_25t';
    const VEHICLE_45T = 'truck_45t';
    const VEHICLE_110T = 'truck_110t';

    // Статусы
    const STATUS_DRAFT = 'draft';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_ACCEPTED = 'accepted';

    const TYPE_DIRECT = 'direct';

    const SCENARIO_LESSOR_PLATFORM = 'lessor_platform';
    const SCENARIO_PLATFORM_DIRECT = 'platform_direct';

    protected $fillable = [
        'document_number',
        'issue_date',
        'type',
        'order_id',
        'order_item_id',
        'sender_company_id',
        'receiver_company_id',
        'delivery_from_id',
        'delivery_to_id',
        'cargo_description',
        'cargo_weight',
        'cargo_value',
        'transport_type',
        'equipment_condition',
        'driver_name',
        'vehicle_model',
        'vehicle_number',
        'driver_contact',
        'distance_km', // Добавлено
        'calculated_cost', // Добавлено
        'sender_signature_path',
        'carrier_signature_path',
        'receiver_signature_path',
        'document_path',
        'status',
        'is_mirror',
        'original_note_id'
    ];

    protected $casts = [
        'delivery_date' => 'date'
    ];

    protected $attributes = [
        'type' => self::TYPE_DIRECT // Значение по умолчанию
    ];

    public static function types(): array
    {
        return [
            self::TYPE_LESSOR_TO_PLATFORM => 'От арендодателя платформе',
            self::TYPE_PLATFORM_TO_LESSEE => 'От платформы арендатору',
            self::TYPE_DIRECT => 'Прямая доставка от арендодателя к арендатору'
        ];
    }


    public static function vehicleTypes(): array
    {
        return [
            self::VEHICLE_25T => 'До 25 тонн (200 руб/км)',
            self::VEHICLE_45T => 'До 45 тонн (250 руб/км)',
            self::VEHICLE_110T => 'До 110 тонн (350 руб/км)',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_IN_TRANSIT => 'В пути',
            self::STATUS_DELIVERED => 'Доставлено',
            self::STATUS_ACCEPTED => 'Принято',
        ];
    }

    public function senderCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sender_company_id');
    }

    public function receiverCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'receiver_company_id');
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

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function isFullySigned(): bool
    {
        return match($this->type) {
            self::TYPE_LESSOR_TO_PLATFORM =>
                !empty($this->sender_signature_path) &&
                !empty($this->carrier_signature_path),

            self::TYPE_PLATFORM_TO_LESSEE =>
                !empty($this->carrier_signature_path) &&
                !empty($this->receiver_signature_path),

            default => false
        };
    }

    public function isComplete(): bool
    {
        return !empty($this->driver_name)
            && !empty($this->vehicle_model)
            && !empty($this->vehicle_number)
            && !empty($this->driver_contact)
            && !empty($this->departure_time);
    }

    public function canBeClosed(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->isComplete();
    }

    public function carrierCompany()
    {
        return $this->belongsTo(Company::class, 'carrier_company_id');
    }

    // Мутаторы для сценариев
    public function getScenarioDetailsAttribute(): array
    {
        return match($this->delivery_scenario) {
            self::SCENARIO_LESSOR_PLATFORM => [
                'sender_role' => 'Арендодатель',
                'receiver_role' => 'Платформа',
                'carrier_role' => 'Арендодатель'
            ],
            self::SCENARIO_PLATFORM_DIRECT => [
                'sender_role' => 'Платформа',
                'receiver_role' => 'Арендатор',
                'carrier_role' => 'Сторонний перевозчик'
            ]
        };
    }

    public function createMirrorNote(): DeliveryNote
    {
        return DeliveryNote::create([
            'original_note_id' => $this->id,
            'delivery_scenario' => $this->delivery_scenario,
            'type' => DeliveryNote::TYPE_PLATFORM_TO_LESSEE,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'sender_company_id' => Platform::main()->id,
            'receiver_company_id' => $this->order->lessee_company_id,
            'delivery_from_id' => $this->delivery_to_id, // Из склада платформы
            'delivery_to_id' => $this->order->delivery_location_id,
            'cargo_description' => $this->cargo_description,
            'cargo_weight' => $this->cargo_weight,
            'cargo_value' => $this->cargo_value,
            'transport_type' => $this->transport_type,
            'is_mirror' => true,
            'status' => self::STATUS_DRAFT
        ]);
    }
}

