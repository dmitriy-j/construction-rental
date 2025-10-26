<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Models\WaybillShift;
use Carbon\Carbon; // Добавлен импорт DB
use Illuminate\Http\Request; // Добавлен импорт Log
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftController extends Controller
{
    public function update(WaybillShift $shift, Request $request)
    {
        Log::info('Shift update initiated', [
            'shift_id' => $shift->id,
            'user_id' => auth()->id(),
            'input' => $request->all(),
            'waybill_status' => $shift->waybill->status,
            'original_shift_data' => $shift->toArray(),
        ]);

        // Проверка прав доступа
        if ($shift->waybill->order->lessor_company_id !== auth()->user()->company_id) {
            Log::warning('Unauthorized shift update attempt', [
                'shift_id' => $shift->id,
                'user_company' => auth()->user()->company_id,
                'order_company' => $shift->waybill->order->lessor_company_id,
            ]);
            abort(403, 'Доступ запрещен');
        }

        try {
            // Нормализация времени (удаление секунд)
            $request->merge([
                'work_start_time' => $request->work_start_time ? substr($request->work_start_time, 0, 5) : null,
                'work_end_time' => $request->work_end_time ? substr($request->work_end_time, 0, 5) : null,
            ]);

            // Валидация
            $validated = $request->validate([
                'object_name' => 'required|string|max:255',
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

            Log::debug('Shift data validated', $validated);

            // Рассчитываем часы работы
            $start = Carbon::createFromFormat('H:i', $validated['work_start_time'], 'Europe/Moscow');
            $end = Carbon::createFromFormat('H:i', $validated['work_end_time'], 'Europe/Moscow');

            if ($end < $start) {
                $end->addDay();
                Log::debug('Adjusted end time for overnight shift', [
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                ]);
            }

            $hoursWorked = round($end->diffInMinutes($start) / 60, 2);
            $validated['hours_worked'] = $hoursWorked;
            Log::debug('Calculated hours worked', [
                'hours' => $hoursWorked,
                'start' => $validated['work_start_time'],
                'end' => $validated['work_end_time'],
            ]);

            // Проверка максимальной длительности смены
            $maxHours = $shift->waybill->rentalCondition->shift_hours ?? 12;
            if ($hoursWorked > $maxHours) {
                Log::warning('Shift duration exceeds maximum', [
                    'calculated_hours' => $hoursWorked,
                    'max_hours' => $maxHours,
                ]);

                return back()->withErrors("Максимальное количество часов в смене: $maxHours");
            }

            // Логирование перед сохранением
            Log::debug('Attempting to update shift', [
                'shift_id' => $shift->id,
                'data' => $validated,
            ]);

            DB::transaction(function () use ($shift, $validated, $hoursWorked) {
                // Временное отключение observer
                $shift->withoutEvents(function () use ($shift, $validated) {
                    $shift->update($validated);
                });

                // Пересчет суммы смены
                $shift->update([
                    'total_amount' => $hoursWorked * $shift->hourly_rate,
                ]);

                // Автоматическое изменение статуса путевого листа
                $waybill = $shift->waybill;
                if ($waybill->status === Waybill::STATUS_FUTURE && $hoursWorked > 0) {
                    $waybill->update(['status' => Waybill::STATUS_ACTIVE]);

                    Log::info('Waybill status updated to active', [
                        'waybill_id' => $waybill->id,
                        'shift_id' => $shift->id,
                        'hours_worked' => $hoursWorked,
                    ]);
                }

                // Пересчитываем общие показатели путевого листа
                $this->recalculateWaybillTotals($waybill);
            });

            Log::info('Shift successfully updated', [
                'shift_id' => $shift->id,
                'changes' => $shift->getChanges(),
            ]);

            // Обработка кнопки "Сохранить и следующая"
            if ($request->has('save_and_next')) {
                $nextShift = $this->findNextShift($shift);

                if ($nextShift) {
                    return redirect()->route('lessor.waybills.show', [
                        'waybill' => $shift->waybill_id,
                        'shift_id' => $nextShift->id,
                    ])->with('success', 'Данные сохранены. Переходим к следующей смене.');
                }

                return back()->with('success', 'Данные сохранены. Это последняя смена в путевом листе.');
            }

            return back()
                ->with('success', 'Данные смены успешно обновлены')
                ->with('updated_fields', array_keys($validated));

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating shift', [
                'shift_id' => $shift->id,
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            return back()->withErrors($e->errors());

        } catch (\Exception $e) {
            Log::error('Critical error updating shift', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors('Критическая ошибка при сохранении: '.$e->getMessage());
        }
    }

    private function recalculateWaybillTotals(Waybill $waybill)
    {
        $waybill->load('shifts');
        $totalHours = $waybill->shifts->sum('hours_worked');
        $totalAmount = $totalHours * $waybill->lessor_hourly_rate;
    }

    protected function findNextShift(WaybillShift $currentShift): ?WaybillShift
    {
        $waybill = $currentShift->waybill;

        // Сначала ищем смены с той же датой, но большим ID
        $nextShift = $waybill->shifts()
            ->where('shift_date', $currentShift->shift_date)
            ->where('id', '>', $currentShift->id)
            ->where(function ($query) {
                $query->whereNull('hours_worked')
                    ->orWhere('hours_worked', 0);
            })
            ->orderBy('id')
            ->first();

        if ($nextShift) {
            return $nextShift;
        }

        // Затем ищем смены с более поздней датой
        return $waybill->shifts()
            ->where('shift_date', '>', $currentShift->shift_date)
            ->where(function ($query) {
                $query->whereNull('hours_worked')
                    ->orWhere('hours_worked', 0);
            })
            ->orderBy('shift_date')
            ->orderBy('id')
            ->first();
    }

    public function destroy(WaybillShift $shift)
    {
        // Логирование начала операции
        Log::info('Delete shift initiated', [
            'shift_id' => $shift->id,
            'waybill_id' => $shift->waybill_id,
            'user_id' => auth()->id(),
        ]);

        // Усиленная проверка прав
        $user = auth()->user();
        $companyId = $user->company_id;

        if (! $shift->waybill || $shift->waybill->order->lessor_company_id !== $companyId) {
            Log::warning('Unauthorized shift delete attempt', [
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'company_id' => $companyId,
                'order_company' => $shift->waybill->order->lessor_company_id ?? 'null',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        // Проверка статуса
        if ($shift->waybill->status !== Waybill::STATUS_ACTIVE) {
            Log::warning('Attempt to delete shift from inactive waybill', [
                'waybill_status' => $shift->waybill->status,
                'waybill_id' => $shift->waybill->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Можно удалять смены только из активного путевого листа',
            ], 400);
        }

        try {
            DB::transaction(function () use ($shift) {
                $shift->delete();
                Log::info('Shift deleted successfully', [
                    'shift_id' => $shift->id,
                    'waybill_id' => $shift->waybill_id,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Смена удалена',
            ]);

        } catch (\Exception $e) {
            Log::error('Shift deletion failed', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера: '.$e->getMessage(),
            ], 500);
        }
    }
}
