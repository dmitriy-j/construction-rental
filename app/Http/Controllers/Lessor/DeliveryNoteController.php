<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\EquipmentAvailability;
use App\Models\OrderItem;
use App\Services\DeliveryNoteService;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        if ($note->status !== DeliveryNote::STATUS_DRAFT) {
            return redirect()->back()->withErrors('Накладная уже закрыта для редактирования');
        }

        $validated = $request->validate([
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('delivery_notes')->ignore($note->id),
            ],
            'issue_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'transport_driver_name' => 'required|string|max:255',
            'transport_vehicle_model' => 'required|string|max:255',
            'transport_vehicle_number' => 'required|string|max:20',
            'driver_contact' => 'required|string|max:255',
            'departure_time' => 'required|date',
            'equipment_condition' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($note, $validated) {
            $note->update($validated);

            if ($note->type === DeliveryNote::TYPE_LESSOR_TO_PLATFORM) {
                $note->update(['status' => DeliveryNote::STATUS_IN_TRANSIT]);

                $service = app(DeliveryNoteService::class);
                $service->processDeliveryNote($note);
            }
        });

        return redirect()->back()->with('success', 'Данные накладной обновлены');
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
                $service = app(\App\Services\DeliveryNoteService::class);

                // Создаем зеркальную ТН для арендатора
                $mirrorNote = $service->createMirrorNote($note);

                // Обновляем статус позиции заказа
                $orderItem = $note->orderItem;
                $orderItem->update(['status' => OrderItem::STATUS_IN_DELIVERY]);

                // Обновляем статус заказа
                $order = $note->order;
                $order->updateStatusBasedOnItems();

                // Обновляем родительский заказ если существует
                if ($order->parentOrder) {
                    $order->parentOrder->updateStatusBasedOnItems();
                }
            }
        });

        return redirect()->route('lessor.orders.show', $note->order)
            ->with('success', 'Накладная закрыта и отправлена арендатору');
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
                    'expires_at' => $deliveryEndDate,
                ]
            );
        }
    }
}
