<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract; // Добавьте эту строку
use App\Models\Waybill; // Добавьте эту строку
use App\Models\DeliveryNote; // Добавьте эту строку
use App\Models\CompletionAct; // Добавьте эту строку

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

    public function index(Request $request)
    {
        $user = auth()->user();
        $type = $request->query('type', 'contracts');

        $query = match($type) {
            'contracts' => Contract::query(),
            'waybills' => Waybill::query(),
            'delivery_notes' => DeliveryNote::query(),
            'completion_acts' => CompletionAct::query(),
        };

        if ($user->isLessee()) {
            $query->whereHas('order', fn($q) => $q->where('lessee_company_id', $user->company_id));
        } else {
            $query->whereHas('order', fn($q) => $q->where('lessor_company_id', $user->company_id));
        }

        $documents = $query->with(['order.lesseeCompany', 'order.lessorCompany'])
                        ->paginate(10);

        // Определяем тип пользователя для выбора правильной папки шаблонов
        $userType = $user->company->is_lessor ? 'lessor' : 'lessee';
        return view("{$userType}.documents.index", [
            'documents' => $documents,
            'type' => $type,
            'userType' => $userType // Добавим для использования в шаблоне
        ]);
    }

    public function download($id, $type)
    {
        $document = match($type) {
            'contracts' => Contract::findOrFail($id),
            'waybills' => Waybill::findOrFail($id),
            'delivery_notes' => DeliveryNote::findOrFail($id),
            'completion_acts' => CompletionAct::findOrFail($id),
        };

        // Проверка прав доступа
        if (auth()->user()->cannot('view', $document)) {
            abort(403);
        }

        $generatorClass = match($type) {
            'contracts' => ContractPdfGenerator::class,
            'waybills' => WaybillPdfGenerator::class,
            'delivery_notes' => DeliveryNoteGenerator::class,
            'completion_acts' => CompletionActGenerator::class,
        };

        return app($generatorClass)->generate($document);
    }

    public function downloadUPDF(Order $order, $type)
    {
        $generator = new UPDPdfGenerator();

        return match($type) {
            'lessor' => $generator->generateForLessor($order),
            'lessee' => $generator->generateForLessee($order),
            default => abort(404)
        };
    }


}
