<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\WaybillShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Добавлен импорт DB
use Illuminate\Support\Facades\Log; // Добавлен импорт Log
use App\Models\Waybill;
use Carbon\Carbon;


class ShiftController extends Controller
{
    public function update(WaybillShift $shift, Request $request)
    {
        Log::info('Shift update initiated', [
            'shift_id' => $shift->id,
            'user_id' => auth()->id(),
            'input' => $request->all(),
            'waybill_status' => $shift->waybill->status,
            'original_shift_data' => $shift->toArray()
        ]);

        // Проверка прав доступа
        if ($shift->waybill->order->lessor_company_id !== auth()->user()->company_id) {
            Log::warning('Unauthorized shift update attempt', [
                'shift_id' => $shift->id,
                'user_company' => auth()->user()->company_id,
                'order_company' => $shift->waybill->order->lessor_company_id
            ]);
            abort(403, 'Доступ запрещен');
        }

        // Проверка статуса путевого листа
        if ($shift->waybill->status !== Waybill::STATUS_ACTIVE) {
            Log::warning('Attempt to update shift in non-active waybill', [
                'shift_id' => $shift->id,
                'waybill_status' => $shift->waybill->status
            ]);
            return back()->withErrors('Редактирование возможно только для активных путевых листов');
        }

        try {
            // Нормализация времени (удаление секунд)
            $request->merge([
                'work_start_time' => $request->work_start_time ? substr($request->work_start_time, 0, 5) : null,
                'work_end_time' => $request->work_end_time ? substr($request->work_end_time, 0, 5) : null
            ]);

            // Валидация (временно без object_name)
            $validated = $request->validate([
                // 'object_name' => 'required|string|max:255', // Раскомментировать после миграции
                'object_address' => 'required|string|max:255',
                'work_start_time' => 'required|regex:/^\d{2}:\d{2}$/',
                'work_end_time' => 'required|regex:/^\d{2}:\d{2}$/',
                'odometer_start' => 'required|integer|min:0',
                'odometer_end' => 'required|integer|min:0',
                'fuel_start' => 'required|numeric|min:0',
                'fuel_end' => 'required|numeric|min:0',
                'fuel_refilled_liters' => 'nullable|numeric|min:0',
                'downtime_hours' => 'nullable|numeric|min:0',
                'downtime_cause' => 'nullable|string|max:500',
                'fuel_refilled_type' => 'nullable|string|max:50',
                'work_description' => 'nullable|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Временно: удаляем object_name до миграции
            if (array_key_exists('object_name', $validated)) {
                unset($validated['object_name']);
            }

            Log::debug('Shift data validated', $validated);

            // Рассчитываем часы работы
            $start = Carbon::createFromFormat('H:i', $validated['work_start_time'], 'Europe/Moscow');
            $end = Carbon::createFromFormat('H:i', $validated['work_end_time'], 'Europe/Moscow');

            if ($end < $start) {
                $end->addDay();
                Log::debug('Adjusted end time for overnight shift', [
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i')
                ]);
            }

            $hoursWorked = round($end->diffInMinutes($start) / 60, 2);
            $validated['hours_worked'] = $hoursWorked;
            Log::debug('Calculated hours worked', [
                'hours' => $hoursWorked,
                'start' => $validated['work_start_time'],
                'end' => $validated['work_end_time']
            ]);

            // Проверка максимальной длительности смены
            $maxHours = $shift->waybill->rentalCondition->shift_hours ?? 12;
            if ($hoursWorked > $maxHours) {
                Log::warning('Shift duration exceeds maximum', [
                    'calculated_hours' => $hoursWorked,
                    'max_hours' => $maxHours
                ]);
                return back()->withErrors("Максимальное количество часов в смене: $maxHours");
            }

            // Логирование перед сохранением
            Log::debug('Attempting to update shift', [
                'shift_id' => $shift->id,
                'data' => $validated
            ]);

            // Временное отключение observer
            $shift->withoutEvents(function () use ($shift, $validated) {
                $shift->update($validated);
            });

            // Пересчет суммы смены
            $shift->update([
                'total_amount' => $hoursWorked * $shift->hourly_rate
            ]);

            Log::info('Shift successfully updated', [
                'shift_id' => $shift->id,
                'changes' => $shift->getChanges()
            ]);

            return back()
                ->with('success', 'Данные смены успешно обновлены')
                ->with('updated_fields', array_keys($validated));

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating shift', [
                'shift_id' => $shift->id,
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return back()->withErrors($e->errors());

        } catch (\Exception $e) {
            Log::error('Critical error updating shift', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors('Критическая ошибка при сохранении: ' . $e->getMessage());
        }
    }



     public function destroy(WaybillShift $shift)
    {
        // Проверка прав доступа
        if ($shift->waybill->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        // Проверка статуса
        if ($shift->waybill->status !== Waybill::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Можно удалять смены только из активного путевого листа'
            ]);
        }

        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Смена удалена'
        ]);
    }

}
