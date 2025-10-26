<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use App\Models\Location;
use App\Models\OrderItem;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeliveryNoteService
{
    protected $platform;

    public function __construct()
    {
        $this->platform = Platform::first() ?? new Platform;
    }

    /**
     * Создает транспортную накладную для позиции заказа
     */
    public function createForOrderItem(OrderItem $item, string $type): DeliveryNote
    {
        $platform = Platform::getMain();
        $platformCompany = $platform->company;
        if (! $platformCompany) {
            throw new \Exception('Platform company not found');
        }
        $order = $item->order;

        $senderId = ($type === DeliveryNote::TYPE_LESSOR_TO_PLATFORM)
            ? $order->lessor_company_id
            : $platform->id;

        $receiverId = ($type === DeliveryNote::TYPE_LESSOR_TO_PLATFORM)
            ? $platformCompany->id  // Используем ID компании платформы
            : $order->lessee_company_id;

        // Для арендодателя НЕ генерируем номер документа!
        $documentNumber = ($type === DeliveryNote::TYPE_LESSOR_TO_PLATFORM)
            ? null
            : $this->generateDocumentNumber();

        return DeliveryNote::create([
            'document_number' => $documentNumber, // Может быть null для арендодателя
            'issue_date' => now(),
            'type' => $type,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'sender_company_id' => $senderId,
            'receiver_company_id' => $receiverId,
            'delivery_from_id' => $item->delivery_from_id,
            'delivery_to_id' => $item->delivery_to_id,
            'cargo_description' => $item->equipment->title,
            'cargo_weight' => $item->equipment->getNumericSpecValue('Вес'),
            'cargo_value' => $item->total_price,
            'transport_type' => $this->determineVehicleType($item->equipment),
            'status' => DeliveryNote::STATUS_DRAFT,
            'is_mirror' => false,
            'visible_to_lessee' => false,
            // Добавляем данные из заказа
            'distance_km' => $item->distance_km,
            'calculated_cost' => $item->delivery_cost,
        ]);
    }

    public function completeDeliveryNote(DeliveryNote $note, array $data): DeliveryNote
    {
        $note->update([
            'transport_driver_name' => $data['transport_driver_name'],
            'transport_vehicle_model' => $data['transport_vehicle_model'],
            'transport_vehicle_number' => $data['transport_vehicle_number'],
            'driver_contact' => $data['driver_contact'],
            'departure_time' => $data['departure_time'],
            'status' => DeliveryNote::STATUS_READY_FOR_SHIPMENT,
        ]);

        return $note;
    }

    // ЕДИНСТВЕННЫЙ экземпляр этого метода!
    public function generateDocumentNumber(): string
    {
        return 'TN-'.Carbon::now()->format('Ymd').'-'.Str::upper(Str::random(6));
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

    protected function determineVehicleType(Equipment $equipment): string
    {
        $weight = $equipment->getNumericSpecValue('Вес');

        if ($weight <= 25) {
            return DeliveryNote::VEHICLE_25T;
        }
        if ($weight <= 45) {
            return DeliveryNote::VEHICLE_45T;
        }

        return DeliveryNote::VEHICLE_110T;
    }

    protected function getVehicleModel(string $type): string
    {
        return match ($type) {
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
            'currentDate' => now()->format('d.m.Y'),
        ]);

        return $pdf->output();
    }

    /**
     * Сохранение PDF в хранилище
     */
    protected function savePdf(DeliveryNote $note): string
    {
        $pdfContent = $this->UPDPdfGenerator($note);

        $fileName = 'delivery_notes/'.$note->document_number.'.pdf';
        Storage::put($fileName, $pdfContent);

        return $fileName;
    }

    /**
     * УНИФИЦИРОВАННЫЙ метод создания зеркальной накладной
     */
    public function createMirrorNote(DeliveryNote $originalNote): DeliveryNote
    {
        $platform = Platform::getMain();
        $platformCompany = $platform->company;
        $order = $originalNote->order;

        \Log::debug('Creating mirror note start', [
            'original_note_id' => $originalNote->id,
            'order_id' => $order->id,
        ]);

        // Проверка наличия родительского заказа
        if (! $parentOrder = $order->parentOrder) {
            \Log::error('Parent order not found for delivery note', [
                'order_id' => $order->id,
                'original_note_id' => $originalNote->id,
            ]);
            throw new \Exception('Parent order not found for delivery note');
        }

        // Проверка критических полей
        if (! $originalNote->order_item_id) {
            \Log::error('Missing order_item_id for delivery note', [
                'note_id' => $originalNote->id,
            ]);
            throw new \Exception('Missing order_item_id for delivery note');
        }

        if (! $originalNote->delivery_from_id || ! $originalNote->delivery_to_id) {
            \Log::error('Missing delivery addresses', [
                'note_id' => $originalNote->id,
                'delivery_from_id' => $originalNote->delivery_from_id,
                'delivery_to_id' => $originalNote->delivery_to_id,
            ]);
            throw new \Exception('Delivery addresses are required');
        }

        // Создаем экземпляр модели
        $mirrorNote = DeliveryNote::create([
            'document_number' => $this->generateDocumentNumber(),
            'issue_date' => now(),
            'type' => DeliveryNote::TYPE_PLATFORM_TO_LESSEE,
            'order_id' => $parentOrder->id,
            'order_item_id' => $originalNote->order_item_id,
            'sender_company_id' => $platformCompany->id,
            'receiver_company_id' => $parentOrder->lessee_company_id,
            'delivery_from_id' => $originalNote->delivery_from_id,
            'delivery_to_id' => $originalNote->delivery_to_id,
            'cargo_description' => $originalNote->cargo_description,
            'cargo_weight' => $originalNote->cargo_weight,
            'cargo_value' => $originalNote->cargo_value,
            'transport_type' => $originalNote->transport_type,
            'equipment_condition' => $originalNote->equipment_condition,
            'status' => DeliveryNote::STATUS_IN_TRANSIT,
            'is_mirror' => true,
            'visible_to_lessee' => true,
            'original_note_id' => $originalNote->id,
            'distance_km' => $originalNote->distance_km,
            'calculated_cost' => $originalNote->calculated_cost,
            'transport_driver_name' => $originalNote->transport_driver_name,
            'transport_vehicle_model' => $originalNote->transport_vehicle_model,
            'transport_vehicle_number' => $originalNote->transport_vehicle_number,
            'driver_contact' => $originalNote->driver_contact,
            'departure_time' => $originalNote->departure_time,
        ]);

        // Логируем успешное создание
        \Log::info('Mirror note created successfully', [
            'id' => $mirrorNote->id,
            'document_number' => $mirrorNote->document_number,
            'order_id' => $parentOrder->id,
            'lessee_company_id' => $parentOrder->lessee_company_id,
        ]);

        return $mirrorNote;
    }

    public function generatePdf(DeliveryNote $note): string
    {
        $pdf = app('dompdf.wrapper');

        // Выбираем шаблон в зависимости от типа ТН
        $template = $note->type === DeliveryNote::TYPE_PLATFORM_TO_LESSEE
            ? 'documents.delivery-note-lessee'
            : 'documents.delivery-note';

        $pdf->loadView($template, [
            'note' => $note,
            'platform' => Platform::getMain(),
            'currentDate' => now()->format('d.m.Y'),
        ]);

        return $pdf->output();
    }

    public function processDeliveryNote(DeliveryNote $note): void
    {

        \Log::debug('Creating mirror note', [
            'original_note_id' => $note->id,
            'order_id' => $note->order_id,
            'order_item_id' => $note->order_item_id,
            'status' => DeliveryNote::STATUS_IN_TRANSIT,
            'is_mirror' => true,
            'visible_to_lessee' => true,
        ]);

        // Создаем зеркальную накладную
        $mirrorNote = $this->createMirrorNote($note);

        // Логируем результат создания
        \Log::debug('Mirror note created', [
            'id' => $mirrorNote->id,
            'document_number' => $mirrorNote->document_number,
            'status' => $mirrorNote->status,
        ]);

        // Обновляем статус позиции заказа
        $orderItem = $note->orderItem;
        $orderItem->update(['status' => OrderItem::STATUS_IN_DELIVERY]);

        // Обновляем статус оборудования
        app(EquipmentAvailabilityService::class)->bookEquipment(
            $orderItem->equipment,
            $note->departure_time->format('Y-m-d'),
            $note->delivery_date ? $note->delivery_date->format('Y-m-d') : now()->addDays(3)->format('Y-m-d'),
            $note->order_id,
            EquipmentAvailability::STATUS_DELIVERY
        );

        // Обновляем статус заказа
        $note->order->updateStatusBasedOnItems();

        if ($parentOrder = $note->order->parentOrder) {
            $parentOrder->updateStatusBasedOnItems();
        }
    }
}
