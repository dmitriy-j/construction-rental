<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\OrderItem;
use App\Models\Platform;
use App\Models\Location;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeliveryNoteService
{
    protected $platform;

    public function __construct()
    {
        $this->platform = Platform::first() ?? new Platform();
    }

    /**
     * Создает транспортную накладную для позиции заказа
     */
    public function createForOrderItem(OrderItem $item): DeliveryNote
    {
        // Добавляем детальное логирование
        Log::debug('Creating delivery note for order item', [
            'item_id' => $item->id,
            'order_id' => $item->order_id,
            'lessor_company_id' => $item->order->lessor_company_id,
            'lessee_company_id' => $item->order->lessee_company_id,
            'item_data' => $item->toArray(),
            'order_data' => $item->order ? $item->order->toArray() : null
        ]);

        // Явно загружаем оборудование со спецификациями
        $item->load([
            'equipment.specifications' => function ($query) {
                $query->select('equipment_id', 'key', 'weight', 'length', 'width', 'height');
            },
            'order'
        ]);

        // Проверяем наличие локаций доставки
        if (!$item->delivery_from_id || !$item->delivery_to_id) {
            throw new \Exception("Локации доставки не указаны для позиции #{$item->id}");
        }

        $fromLocation = Location::find($item->delivery_from_id);
        $toLocation = Location::find($item->delivery_to_id);

        if (!$fromLocation || !$toLocation) {
            throw new \Exception("Локации доставки не найдены");
        }

        // Рассчитываем расстояние
        $distance = app(DeliveryCalculatorService::class)->calculateDistance(
            $fromLocation->latitude,
            $fromLocation->longitude,
            $toLocation->latitude,
            $toLocation->longitude
        );

        // Рассчитываем тип транспортного средства
        $vehicleType = app(TransportCalculatorService::class)
            ->calculateRequiredTransport($item->equipment);

        // Рассчитываем стоимость доставки
        $calculatedCost = $this->calculateCost($vehicleType, $distance);

        // Создаем накладную
        return DeliveryNote::create([
            'document_number' => $this->generateDocumentNumber(),
            'issue_date' => now(),
            'type' => DeliveryNote::TYPE_DIRECT,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'sender_company_id' => $item->order->lessor_company_id,
            'receiver_company_id' => $item->order->lessee_company_id,
            'delivery_from_id' => $item->delivery_from_id,
            'delivery_to_id' => $item->delivery_to_id,
            'cargo_description' => $item->equipment->title,
            'cargo_weight' => $item->equipment->getNumericSpecValue('Вес'),
            'cargo_value' => $item->total_price,
            'transport_type' => $vehicleType,
            'equipment_condition' => 'Хорошее',
            'status' => DeliveryNote::STATUS_DRAFT,
            'distance_km' => $distance,
            'calculated_cost' => $calculatedCost,
            'driver_name' => null,
            'vehicle_model' => null,
            'vehicle_number' => null,
            'driver_contact' => null,
            'sender_signature_path' => null,
            'carrier_signature_path' => null,
            'receiver_signature_path' => null,
            'document_path' => null
        ]);
    }

    public function completeDeliveryNote(DeliveryNote $note, array $data): DeliveryNote
    {
        $note->update([
            'driver_name' => $data['driver_name'],
            'vehicle_model' => $data['vehicle_model'],
            'vehicle_number' => $data['vehicle_number'],
            'driver_contact' => $data['driver_contact'],
            'departure_time' => $data['departure_time'],
            'status' => DeliveryNote::STATUS_READY_FOR_SHIPMENT
        ]);

        return $note;
    }

    protected function createNote(
        OrderItem $item,
        string $type,
        int $senderId,
        int $receiverId,
        ?int $fromLocationId,
        ?int $toLocationId
    ): DeliveryNote {
        // Проверяем наличие локаций
        if (!$fromLocationId || !$toLocationId) {
            throw new \Exception("Missing location for delivery note (type: $type)");
        }

        $from = Location::find($fromLocationId);
        $to = Location::find($toLocationId);

        if (!$from || !$to) {
            throw new \Exception("Location not found (from: $fromLocationId, to: $toLocationId)");
        }

        $distance = app(DeliveryCalculatorService::class)->calculateDistance(
            $from->latitude,
            $from->longitude,
            $to->latitude,
            $to->longitude
        );

        $vehicleType = app(TransportCalculatorService::class)
            ->calculateRequiredTransport($item->equipment);

        return DeliveryNote::create([
            'document_number' => $this->generateDocumentNumber(),
            'issue_date' => now(),
            'type' => $type,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'sender_company_id' => $senderId,
            'receiver_company_id' => $receiverId,
            'delivery_from_id' => $fromLocationId,
            'delivery_to_id' => $toLocationId,
            'cargo_description' => $item->equipment->title,
            'cargo_weight' => $item->equipment->getNumericSpecValue('Вес'),
            'cargo_value' => $item->total_price,
            'transport_type' => $vehicleType,
            'equipment_condition' => 'Хорошее',
            'driver_name' => 'Водитель не назначен',
            'vehicle_model' => $this->getVehicleModel($vehicleType),
            'vehicle_number' => 'А000АА',
            'driver_contact' => '+7 XXX XXX-XX-XX',
            'distance_km' => $distance,
            'calculated_cost' => $this->calculateCost($vehicleType, $distance),
            'status' => DeliveryNote::STATUS_DRAFT
        ]);
    }

    protected function generateDocumentNumber(): string
    {
        return 'TN-' . Carbon::now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    protected function calculateDistance(Location $from, Location $to): float
    {
        return app(DeliveryCalculatorService::class)->calculateDistance(
            $from->latitude,
            $from->longitude,
            $to->latitude,
            $to->longitude
        );
    }

    protected function determineVehicleType(OrderItem $item): string
    {
        $weight = $item->equipment->getNumericSpecValue('Вес');

        if ($weight <= 25) return DeliveryNote::VEHICLE_25T;
        if ($weight <= 45) return DeliveryNote::VEHICLE_45T;
        return DeliveryNote::VEHICLE_110T;
    }

    protected function getVehicleModel(string $type): string
    {
        return match($type) {
            DeliveryNote::VEHICLE_25T => 'КАМАЗ-65115',
            DeliveryNote::VEHICLE_45T => 'МАЗ-7510',
            DeliveryNote::VEHICLE_110T => 'Scania R730',
            default => 'Неизвестная модель'
        };
    }

    protected function calculateCost(string $vehicleType, float $distance): float
    {
        $rates = [
            DeliveryNote::VEHICLE_25T => 200,
            DeliveryNote::VEHICLE_45T => 250,
            DeliveryNote::VEHICLE_110T => 350,
        ];

        return $rates[$vehicleType] * $distance;
    }

    /**
     * Генерация PDF документа
     */
    public function UPDPdfGenerator(DeliveryNote $note): string
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('documents.delivery-note', [
            'note' => $note,
            'platform' => $this->platform,
            'currentDate' => now()->format('d.m.Y')
        ]);

        return $pdf->output();
    }

    /**
     * Сохранение PDF в хранилище
     */
    protected function savePdf(DeliveryNote $note): string
    {
        $pdfContent = $this->UPDPdfGenerator($note);

        $fileName = 'delivery_notes/' . $note->document_number . '.pdf';
        Storage::put($fileName, $pdfContent);

        return $fileName;
    }

    public function createMirrorNote(DeliveryNote $originalNote): DeliveryNote
    {
        // Определяем тип зеркальной накладной
        $mirrorType = match($originalNote->type) {
            DeliveryNote::TYPE_LESSOR_TO_PLATFORM => DeliveryNote::TYPE_PLATFORM_TO_LESSEE,
            default => DeliveryNote::TYPE_DIRECT
        };

        return DeliveryNote::create([
            'document_number' => 'MIRROR-' . $originalNote->document_number,
            'issue_date' => now(),
            'type' => $mirrorType,
            'order_id' => $originalNote->order_id,
            'order_item_id' => $originalNote->order_item_id,
            'sender_company_id' => $this->platform->id, // Отправитель - платформа
            'receiver_company_id' => $originalNote->receiver_company_id,
            'delivery_from_id' => $originalNote->delivery_from_id,
            'delivery_to_id' => $originalNote->delivery_to_id,
            'cargo_description' => $originalNote->cargo_description,
            'cargo_weight' => $originalNote->cargo_weight,
            'cargo_value' => $originalNote->cargo_value,
            'transport_type' => $originalNote->transport_type,
            'equipment_condition' => $originalNote->equipment_condition,
            'status' => DeliveryNote::STATUS_DRAFT,
            'is_mirror' => true,
            'original_note_id' => $originalNote->id
        ]);
    }
}
