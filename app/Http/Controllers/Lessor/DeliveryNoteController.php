<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EquipmentAvailabilityService;
use App\Models\EquipmentAvailability;

class DeliveryNoteController extends Controller
{
    public function edit(DeliveryNote $note)
    {
        // Проверка прав
        if ($note->sender_company_id !== auth()->user()->company_id || $note->is_mirror) {
            abort(403);
        }

        return view('lessor.documents.delivery-note-edit', compact('note'));
    }

    public function update(DeliveryNote $note, Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            // Используем корректные имена полей:
            'driver_name' => 'required|string|max:255',
            'vehicle_model' => 'required|string|max:255',
            'vehicle_number' => 'required|string|max:20',
            'driver_contact' => 'required|string|max:255',
            'departure_time' => 'required|date', // Новое поле
            'cargo_condition' => 'required|string|max:255',
            'distance_km' => 'required|numeric|min:0',
            'calculated_cost' => 'required|numeric|min:0',
        ]);

        // Маппинг на правильные имена в БД
        $mappedData = [
            'document_number' => $validated['document_number'],
            'issue_date' => $validated['issue_date'],
            'delivery_date' => $validated['delivery_date'],
            'transport_driver_name' => $validated['driver_name'], // Правильное имя
            'transport_vehicle_model' => $validated['vehicle_model'], // Правильное имя
            'transport_vehicle_number' => $validated['vehicle_number'], // Правильное имя
            'driver_contact' => $validated['driver_contact'],
            'departure_time' => $validated['departure_time'],
            'equipment_condition' => $validated['cargo_condition'], // Правильное имя
            'distance_km' => $validated['distance_km'],
            'calculated_cost' => $validated['calculated_cost']
        ];

        DB::transaction(function() use ($note, $mappedData) {
            $note->update($mappedData);

            if ($note->isComplete()) {
                $note->update(['status' => DeliveryNote::STATUS_IN_TRANSIT]);
                // Вместо обновления equipment->global_status
                $this->updateEquipmentStatus($note);
            }
        });

        return redirect()->route('lessor.orders.show', $note->order)
            ->with('success', 'Данные накладной обновлены');
    }

    private function updateEquipmentStatus(DeliveryNote $note)
    {
        $service = app(EquipmentAvailabilityService::class);
        $orderItem = $note->orderItem;

        if ($orderItem && $orderItem->equipment) {
            $service->bookEquipment(
                $orderItem->equipment,
                $note->departure_time->format('Y-m-d'),
                $note->delivery_date->format('Y-m-d'),
                $note->order_id,
                EquipmentAvailability::STATUS_DELIVERY // Используем константу из модели
            );
        }
    }

    public function close(DeliveryNote $note)
    {
        DB::transaction(function () use ($note) {
            $note->update(['status' => DeliveryNote::STATUS_DELIVERED]);

            if ($note->type === DeliveryNote::TYPE_LESSOR_TO_PLATFORM) {
                // Создаем зеркальную ТН для арендатора
                $mirrorNote = $note->createMirrorNote();

                // Обновляем статус позиции заказа
                $orderItem = $note->orderItem;
                $orderItem->update([
                    'status' => OrderItem::STATUS_IN_DELIVERY // Явное обновление статуса
                ]);

                // Обновляем статус заказа через агрегацию
                $order = $note->order;
                $order->updateStatusBasedOnItems();

                // Обновляем родительский заказ если существует
                if ($order->parentOrder) {
                    $order->parentOrder->updateStatusBasedOnItems();
                }

                event(new DeliveryNoteCreated($mirrorNote));
            }
        });
    }

    private function completeDeliveryStatus(DeliveryNote $note)
    {
        $service = app(EquipmentAvailabilityService::class);
        $orderItem = $note->orderItem;

        if ($orderItem && $orderItem->equipment) {
            // Обновляем статус оборудования на "доставлено"
            $service->bookEquipment(
                $orderItem->equipment,
                $note->delivery_date->format('Y-m-d'),
                $note->delivery_date->format('Y-m-d'),
                $note->order_id,
                EquipmentAvailability::STATUS_ACTIVE
            );
        }
    }

    private function updateEquipmentDeliveryStatus(DeliveryNote $note)
    {
        $equipmentIds = $note->order->items->pluck('equipment_id');
        $deliveryDays = $note->distance_km / 500; // Пример: 500 км в день
        $deliveryEndDate = now()->addDays(ceil($deliveryDays));

        foreach ($equipmentIds as $equipmentId) {
            EquipmentAvailability::updateOrCreate(
                [
                    'equipment_id' => $equipmentId,
                    'date' => now()->format('Y-m-d'),
                ],
                [
                    'status' => EquipmentAvailability::STATUS_DELIVERY,
                    'order_id' => $note->order_id,
                    'expires_at' => $deliveryEndDate
                ]
            );
        }
    }

}
