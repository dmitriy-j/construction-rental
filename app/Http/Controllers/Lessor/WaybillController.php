<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Waybill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;

class WaybillController extends Controller
{
    public function index(Order $order)
    {
        // Авторизация через политику с передачей заказа
        $this->authorize('viewAny', [Waybill::class, $order]);

        // Остальной код без изменений
        $waybills = $order->waybills()
            ->with(['equipment', 'operator', 'rentalCondition'])
            ->orderBy('work_date')
            ->orderBy('shift')
            ->get();

        return view('lessor.documents.waybills.index', [
            'order' => $order,
            'waybills' => $waybills,
            'type' => 'waybills'
        ]);
    }

    public function show(Waybill $waybill)
    {
        // Проверка прав доступа
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $waybill->load([
            'order.items',
            'equipment.mainImage',
            'operator',
            'rentalCondition'
        ]);

        return view('lessor.documents.waybills.show', compact('waybill'));
    }

    public function update(Request $request, Waybill $waybill)
    {
        // Проверка прав доступа
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        // Проверка статуса (можно редактировать только созданные и в процессе)
        if (!in_array($waybill->status, [Waybill::STATUS_CREATED, Waybill::STATUS_IN_PROGRESS])) {
            return back()->withErrors('Невозможно изменить завершенный путевой лист');
        }

        $validated = $request->validate([
            'odometer_start' => 'required|integer|min:0',
            'odometer_end' => 'required|integer|gt:odometer_start',
            'fuel_start' => 'required|numeric|min:0',
            'fuel_end' => 'required|numeric|min:0|lt:fuel_start',
            'hours_worked' => 'required|numeric|min:0|max:24',
            'downtime_hours' => 'nullable|numeric|min:0',
            'downtime_cause' => 'nullable|string|max:500',
            'work_description' => 'nullable|string',
            'notes' => 'nullable|string|max:1000'
        ]);

        $waybill->update([
            ...$validated,
            'status' => Waybill::STATUS_IN_PROGRESS
        ]);

        return back()->with('success', 'Данные путевого листа обновлены');
    }

    public function sign(Request $request, Waybill $waybill)
    {
        // Проверка прав доступа
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $request->validate([
            'signature' => 'required|string'
        ]);

        $signaturePath = $this->saveSignature($request->signature);

        $waybill->update([
            'customer_signature_path' => $signaturePath,
            'status' => Waybill::STATUS_COMPLETED,
            'completed_at' => now()
        ]);

        return response()->json(['status' => 'success']);
    }

    public function download(Waybill $waybill)
    {
        // Проверка прав доступа
        if ($waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $waybill->load([
            'order.lesseeCompany',
            'order.lessorCompany',
            'equipment',
            'operator',
            'rentalCondition'
        ]);

        $pdf = PDF::loadView('lessor.documents.waybills.pdf', compact('waybill'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Путевой-лист-ЭСМ-2-{$waybill->id}.pdf");
    }

    private function saveSignature(string $svg): string
    {
        $filename = 'signatures/' . Str::uuid() . '.svg';
        Storage::disk('public')->put($filename, $svg);
        return $filename;
    }
}
