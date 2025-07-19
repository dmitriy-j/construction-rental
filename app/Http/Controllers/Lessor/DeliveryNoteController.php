<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    public function edit(DeliveryNote $note)
    {
        // Проверка прав
        if ($note->sender_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        return view('lessor.documents.delivery-note-edit', compact('note'));
    }

    public function update(Request $request, DeliveryNote $note)
    {
        // Проверка прав
        if ($note->sender_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'driver_name' => 'required|string|max:255',
            'vehicle_model' => 'required|string|max:255',
            'vehicle_number' => 'required|string|max:20',
            'driver_contact' => 'required|string|max:255',
            'departure_time' => 'required|date',
            'cargo_condition' => 'required|string|max:255',
        ]);

        DB::transaction(function() use ($note, $validated) {
            $note->update($validated);

            // Если все поля заполнены, меняем статус на "в пути"
            if ($note->isComplete()) {
                $note->update(['status' => DeliveryNote::STATUS_IN_TRANSIT]);

                // Обновляем статус оборудования
                Equipment::whereIn('id', $note->order->items->pluck('equipment_id'))
                    ->update(['global_status' => 'in_transit']);
            }
        });

        return redirect()->route('lessor.orders.show', $note->order)
            ->with('success', 'Данные накладной обновлены');
    }

    public function close(DeliveryNote $note)
    {
        if ($note->sender_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        if (!$note->canBeClosed()) {
            return back()->withErrors('Невозможно закрыть накладную. Заполните все обязательные поля.');
        }

        DB::transaction(function() use ($note) {
            // Обновляем статус накладной
            $note->update(['status' => DeliveryNote::STATUS_DELIVERED]);

            // Обновляем статус оборудования в таблице доступности
            $this->updateEquipmentDeliveryStatus($note);

            // Обновляем дату начала услуг в заказе
            $note->order->update([
                'service_start_date' => now()
            ]);
        });

        return redirect()->route('lessor.orders.show', $note->order)
            ->with('success', 'Накладная закрыта. Техника в пути!');
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
