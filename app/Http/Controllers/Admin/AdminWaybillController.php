<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\Waybill;
use App\Models\WaybillShift;
use Illuminate\Http\Request;

class AdminWaybillController extends Controller
{
    public function show(Waybill $waybill)
    {
        $waybill->load('orderItem.equipment', 'operator', 'shifts');
        $operators = Operator::with('equipment')->where('is_active', true)->orderBy('full_name')->get();
        return view('admin.waybills.show', compact('waybill', 'operators'));
    }

    public function update(Request $request, Waybill $waybill)
    {
        $rules = [
            'operator_id' => 'nullable|exists:operators,id',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];

        $shiftData = $request->input('shifts', []);
        foreach (array_keys($shiftData) as $shiftId) {
            $rules['shifts.' . $shiftId . '.hours'] = 'nullable|numeric|min:0|max:24';
        }

        $data = $request->validate($rules);

        if (array_key_exists('operator_id', $data)) {
            $waybill->operator_id = $data['operator_id'] ?: null;
        }
        if (!empty($data['end_date'])) {
            $waybill->end_date = $data['end_date'];
        }
        $waybill->save();

        foreach ($shiftData as $shiftId => $payload) {
            WaybillShift::where('id', $shiftId)->update([
                'operator_id' => $data['operator_id'] ?? null,
                'hours_worked' => $payload['hours'] ?? 0,
            ]);
        }

        return redirect()->route('admin.waybills.show', $waybill)->with('success', 'Путевой лист сохранен');
    }

    public function close(Waybill $waybill)
    {
        if ($waybill->status !== Waybill::STATUS_ACTIVE && $waybill->status !== Waybill::STATUS_FUTURE) {
            return redirect()->back()->with('error', 'Путевой лист уже закрыт');
        }

        $waybill->status = Waybill::STATUS_COMPLETED;
        $waybill->save();

        app(\App\Services\WaybillCreationService::class)->createNextWaybill($waybill);

        return redirect()->route('admin.waybills.show', $waybill)->with('success', 'Путевой лист закрыт, следующий период создан');
    }
}
