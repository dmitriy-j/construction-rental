<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Добавлено
// Добавлено

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
        'transport_driver_name',
        'transport_vehicle_model',
        'transport_vehicle_number',
        'equipment_condition',
        'departure_time',
        'driver_contact',
        'distance_km', // Добавлено
        'calculated_cost', // Добавлено
        'departure_time',
        'cargo_condition',
        'sender_signature_path',
        'carrier_signature_path',
        'receiver_signature_path',
        'document_path',
        'status',
        'is_mirror',
        'original_note_id',
        'delivery_date',
        'visible_to_lessee',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'delivery_date' => 'date',
        'departure_time' => 'datetime',
        'visible_to_lessee' => 'boolean',
    ];

    protected $attributes = [
        'type' => self::TYPE_DIRECT, // Значение по умолчанию
        'delivery_date' => null, // Значение по умолчанию
        'document_number' => null,
        'issue_date' => null,
    ];

    public static function types(): array
    {
        return [
            self::TYPE_LESSOR_TO_PLATFORM => 'От арендодателя платформе',
            self::TYPE_PLATFORM_TO_LESSEE => 'От платформы арендатору',
            self::TYPE_DIRECT => 'Прямая доставка от арендодателя к арендатору',
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

    public function getStatusTextAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
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

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('sender_company_id', $companyId)
                ->orWhere('receiver_company_id', $companyId);
        });
    }

    public function isFullySigned(): bool
    {
        return match ($this->type) {
            self::TYPE_LESSOR_TO_PLATFORM => ! empty($this->sender_signature_path) &&
                ! empty($this->carrier_signature_path),

            self::TYPE_PLATFORM_TO_LESSEE => ! empty($this->carrier_signature_path) &&
                ! empty($this->receiver_signature_path),

            default => false
        };
    }

    public function isComplete(): bool
    {
        return ! empty($this->document_number) &&
            ! empty($this->issue_date) &&
            ! empty($this->transport_driver_name) &&
            ! empty($this->transport_vehicle_model) &&
            ! empty($this->transport_vehicle_number) &&
            ! empty($this->driver_contact) &&
            ! empty($this->departure_time);
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
        return match ($this->delivery_scenario) {
            self::SCENARIO_LESSOR_PLATFORM => [
                'sender_role' => 'Арендодатель',
                'receiver_role' => 'Платформа',
                'carrier_role' => 'Арендодатель',
            ],
            self::SCENARIO_PLATFORM_DIRECT => [
                'sender_role' => 'Платформа',
                'receiver_role' => 'Арендатор',
                'carrier_role' => 'Сторонний перевозчик',
            ]
        };
    }

    public function createMirrorNote(): DeliveryNote
    {
        $platform = Platform::getMain();

        return DeliveryNote::create([
            'document_number' => app(DeliveryNoteService::class)->generateDocumentNumber(),
            'issue_date' => now(),
            'type' => DeliveryNote::TYPE_PLATFORM_TO_LESSEE,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'sender_company_id' => $platform->id, // Платформа как юр. отправитель
            'receiver_company_id' => $this->order->lessee_company_id, // Арендатор
            // Физические адреса остаются теми же!
            'delivery_from_id' => $this->delivery_from_id, // Факт. место погрузки (техника)
            'delivery_to_id' => $this->delivery_to_id,     // Факт. место разгрузки (стройка)
            'cargo_description' => $this->cargo_description,
            'cargo_weight' => $this->cargo_weight,
            'cargo_value' => $this->cargo_value,
            'transport_type' => $this->transport_type,
            'equipment_condition' => $this->equipment_condition,
            'status' => DeliveryNote::STATUS_IN_TRANSIT,
            'is_mirror' => true,
            'visible_to_lessee' => true,
            'original_note_id' => $this->id,
            'distance_km' => $this->distance_km,
            'calculated_cost' => $this->calculated_cost,
            'transport_driver_name' => $this->transport_driver_name,
            'transport_vehicle_model' => $this->transport_vehicle_model,
            'transport_vehicle_number' => $this->transport_vehicle_number,
            'driver_contact' => $this->driver_contact,
            'departure_time' => $this->departure_time,
        ]);
    }

    public function fillDeliveryDetails(array $data)
    {
        $this->update([
            'document_number' => $data['document_number'],
            'issue_date' => $data['issue_date'],
            'driver_name' => $data['driver_name'],
            'vehicle_model' => $data['vehicle_model'],
            'vehicle_number' => $data['vehicle_number'],
            'driver_contact' => $data['driver_contact'],
            'status' => DeliveryNote::STATUS_IN_TRANSIT,
        ]);
    }

    public function completeDelivery(Carbon $deliveryDate)
    {
        $this->update([
            'delivery_date' => $deliveryDate,
            'status' => DeliveryNote::STATUS_DELIVERED,
        ]);
    }
}
