<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\Waybill;
use App\Models\WaybillShift;
use Illuminate\Http\Request;

class AdminWaybillController extends Controller
{
    public function show(Waybill $waybill, Request $request)
    {
        $waybill->load([
            'orderItem.equipment',
            'order',
            'operator',
            'shifts.operator',
        ]);

        // Если у ПЛ нет смен — создаём их на период
        if ($waybill->shifts->isEmpty()) {
            app(\App\Services\WaybillCreationService::class)->createShiftsForWaybill($waybill);
            $waybill->load('shifts.operator');
        }

        $operators = Operator::with('equipment')->where('is_active', true)->orderBy('full_name')->get();

        // Выбор текущей смены
        $selectedShift = null;
        if ($request->has('shift_id')) {
            $selectedShift = $waybill->shifts->find($request->shift_id);
        }
        if (! $selectedShift) {
            $selectedShift = $waybill->shifts->firstWhere('hours_worked', 0) ?? $waybill->shifts->first();
        }

        // Автозаполнение из предыдущей смены
        if ($selectedShift && (empty($selectedShift->odometer_start) || empty($selectedShift->fuel_start))) {
            $previousShift = WaybillShift::where('waybill_id', $waybill->id)
                ->where('shift_date', '<', $selectedShift->shift_date)
                ->whereNotNull('odometer_end')
                ->whereNotNull('fuel_end')
                ->orderBy('shift_date', 'desc')
                ->first();

            if ($previousShift) {
                $selectedShift->odometer_start = $previousShift->odometer_end;
                $selectedShift->fuel_start = $previousShift->fuel_end + ($previousShift->fuel_refilled_liters ?? 0);
            }
        }

        // Статистика
        $totalShifts = $waybill->shifts->count();
        $filledShifts = $waybill->shifts->where('hours_worked', '>', 0)->count();
        $totalHours = $waybill->shifts->sum('hours_worked');
        $baseHourlyRate = $waybill->lessor_hourly_rate ?: ($waybill->orderItem?->rentalTerm?->price_per_hour ?: 0);

        return view('admin.waybills.show', compact(
            'waybill', 'operators', 'selectedShift', 'totalShifts', 'filledShifts', 'totalHours', 'baseHourlyRate'
        ));
    }

    public function update(Request $request, Waybill $waybill)
    {
        $request->validate([
            'operator_id' => 'nullable|exists:operators,id',
            'shift_id' => 'required|exists:waybill_shifts,id',
            'shift_date' => 'nullable|date',
            'object_name' => 'nullable|string|max:255',
            'object_address' => 'nullable|string|max:255',
            'departure_time' => 'nullable',
            'return_time' => 'nullable',
            'odometer_start' => 'nullable|numeric|min:0',
            'odometer_end' => 'nullable|numeric|min:0',
            'fuel_start' => 'nullable|numeric|min:0',
            'fuel_end' => 'nullable|numeric|min:0',
            'fuel_refilled_liters' => 'nullable|numeric|min:0',
            'fuel_refilled_type' => 'nullable|string|max:50',
            'hours_worked' => 'nullable|numeric|min:0|max:24',
            'downtime_hours' => 'nullable|numeric|min:0|max:24',
            'downtime_cause' => 'nullable|string|max:255',
            'work_description' => 'nullable|string|max:1000',
        ]);

        // Обновляем оператора ПЛ
        if ($request->has('operator_id')) {
            $waybill->operator_id = $request->operator_id ?: null;
            $waybill->save();
        }

        // Обновляем смену
        $shift = WaybillShift::findOrFail($request->shift_id);
        if ($request->has('shift_date')) {
            $shift->shift_date = $request->shift_date;
        }
        $shift->fill($request->only([
            'object_name', 'object_address', 'departure_time', 'return_time',
            'odometer_start', 'odometer_end', 'fuel_start', 'fuel_end',
            'fuel_refilled_liters', 'fuel_refilled_type', 'hours_worked',
            'downtime_hours', 'downtime_cause', 'work_description',
        ]));

        // Авто-расчёт часов из времени работы, если часы не заданы вручную
        if (! $shift->hours_worked && $request->departure_time && $request->return_time) {
            $start = strtotime($request->departure_time);
            $end = strtotime($request->return_time);
            if ($end < $start) { $end += 86400; }
            $shift->hours_worked = round(($end - $start) / 3600, 2);
        }

        // Пересчёт суммы смены
        $rate = $waybill->lessor_hourly_rate ?: ($waybill->orderItem?->rentalTerm?->price_per_hour ?: 0);
        $shift->hourly_rate = $rate;
        $shift->total_amount = round(($shift->hours_worked ?? 0) * $rate, 2);
        // saveQuietly — обходим observer арендодателя (обязательные поля), топливо/одометр опциональны
        $shift->saveQuietly();

        // Автоперенос данных в следующую незаполненную смену
        $nextShift = $waybill->shifts
            ->where('id', '>', $shift->id)
            ->where(fn($s) => empty($s->hours_worked) || $s->hours_worked == 0)
            ->first() ?? $waybill->shifts->where('id', '>', $shift->id)->first();

        if ($nextShift) {
            $nextShift->operator_id = $shift->operator_id ?? $waybill->operator_id;
            $nextShift->object_name = $shift->object_name;
            $nextShift->object_address = $shift->object_address;
            $nextShift->departure_time = $shift->departure_time;
            $nextShift->return_time = $shift->return_time;
            $nextShift->odometer_start = $shift->odometer_end;
            $nextShift->fuel_start = $shift->fuel_end;
            $nextShift->saveQuietly();
        }

        return redirect()->route('admin.waybills.show', ['waybill' => $waybill, 'shift_id' => $shift->id])
            ->with('success', 'Смена сохранена. Данные перенесены в следующую смену.');
    }

    public function storeShift(Request $request, Waybill $waybill)
    {
        $request->validate(['shift_date' => 'required|date']);

        $exists = WaybillShift::where('waybill_id', $waybill->id)
            ->whereDate('shift_date', $request->shift_date)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Смена на эту дату уже существует');
        }

        $shift = new WaybillShift([
            'waybill_id' => $waybill->id,
            'shift_date' => $request->shift_date,
            'operator_id' => $waybill->operator_id,
            'hourly_rate' => $waybill->lessor_hourly_rate,
        ]);
        $shift->saveQuietly();

        return redirect()->route('admin.waybills.show', ['waybill' => $waybill, 'shift_id' => $shift->id])
            ->with('success', 'Смена добавлена');
    }

    public function destroyShift(Request $request, Waybill $waybill, WaybillShift $shift)
    {
        if ($shift->waybill_id !== $waybill->id) {
            abort(404);
        }
        $shift->deleteQuietly();

        return redirect()->route('admin.waybills.show', $waybill)->with('success', 'Смена удалена');
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
