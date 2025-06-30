<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function createDeliveryNote(Order $order)
    {
        // Валидация данных
        $data = request()->validate([
            'delivery_date' => 'required|date',
            'driver_name' => 'required|string',
            'equipment_condition' => 'required|string'
        ]);

        // Создание накладной
        $deliveryNote = $order->deliveryNote()->create($data);

        // Обновление даты начала услуг
        $order->update([
            'service_start_date' => $data['delivery_date']
        ]);

        return response()->json($deliveryNote, 201);
    }

    public function createWaybill(Order $order)
    {
        $data = request()->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'operator_id' => 'required|exists:users,id',
            'work_date' => 'required|date',
            'hours_worked' => 'required|numeric|min:0',
            'downtime_hours' => 'nullable|numeric|min:0',
            'downtime_cause' => 'nullable|in:lessee,lessor,force_majeure'
        ]);

        $waybill = $order->waybills()->create($data);
        return response()->json($waybill, 201);
    }

    public function generateCompletionAct(Order $order)
    {
        if (!$order->canGenerateCompletionAct()) {
            abort(400, 'Невозможно сформировать акт для этого заказа');
        }

        // Создаем экземпляр генератора и вызываем метод
        $generator = new CompletionActGenerator();
        $act = $generator->generateForOrder($order);

        return response()->json($act, 201);
    }
}
