<?php

namespace App\Observers;

use App\Models\WaybillShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WaybillShiftObserver
{
    public function creating(WaybillShift $shift)
    {
        // Автоматическая привязка к оператору из путевого листа
        if (! $shift->operator_id && $shift->waybill) {
            $shift->operator_id = $shift->waybill->operator_id;
        }
    }

    public function updating(WaybillShift $shift)
    {
        Log::debug('WaybillShift updating', [
            'shift_id' => $shift->id,
            'original' => $shift->getOriginal(),
            'changes' => $shift->getDirty(),
        ]);

        // Автоматическая активация путевого листа при изменении смены
        if ($shift->waybill->status === \App\Models\Waybill::STATUS_FUTURE) {
            \App\Models\Waybill::withoutEvents(function () use ($shift) {
                $shift->waybill->update(['status' => \App\Models\Waybill::STATUS_ACTIVE]);
            });
            $shift->load('waybill'); // Обновляем связь
        }

        // Проверка активности путевого листа
        if ($shift->waybill->status !== \App\Models\Waybill::STATUS_ACTIVE) {
            $error = 'Невозможно обновить смену: путевой лист не активен';
            Log::error($error, [
                'shift_id' => $shift->id,
                'waybill_status' => $shift->waybill->status,
            ]);
            throw new \Exception($error);
        }

        $dirty = $shift->getDirty(); // Получаем измененные поля

        // Проверка заполненности обязательных полей
        $requiredFields = [
            'object_address',
            'work_start_time',
            'work_end_time',
            'odometer_start',
            'odometer_end',
            'fuel_start',
            'fuel_end',
            'hours_worked',
        ];

        foreach ($requiredFields as $field) {
            // Проверяем либо в измененных данных, либо в текущем значении
            if (empty($dirty[$field] ?? $shift->$field)) {
                throw new \Exception("Поле $field обязательно для заполнения");
            }
        }
    }

    public function updated(WaybillShift $shift)
    {
        Log::debug('WaybillShift updated', [
            'shift_id' => $shift->id,
            'changes' => $shift->getChanges(),
        ]);

        // Если акт уже создан - пересчитываем
        if ($act = $shift->waybill->completionAct) {
            Log::info('Updating completion act for shift', [
                'shift_id' => $shift->id,
                'act_id' => $act->id,
            ]);

            $act->update([
                'total_hours' => $shift->waybill->shifts->sum('hours_worked'),
                'total_downtime' => $shift->waybill->shifts->sum('downtime_hours'),
                'total_amount' => $shift->waybill->shifts->sum('total_amount'),
                'final_amount' => $act->total_amount - $act->penalty_amount,
            ]);
        }

        $shift->total_amount = $shift->hours_worked * $shift->waybill->base_hourly_rate;
        $shift->saveQuietly();

        Log::debug('Recalculated shift total amount', [
            'shift_id' => $shift->id,
            'total_amount' => $shift->total_amount,
        ]);
    }

    public function saving(WaybillShift $shift)
    {
        try {
            $waybill = $shift->waybill;

            if (! $shift->work_start_time || ! $shift->work_end_time) {
                Log::warning('Cannot update waybill dates - shift times missing', [
                    'shift_id' => $shift->id,
                ]);

                return;
            }

            $start = Carbon::parse($shift->work_start_time);
            $end = Carbon::parse($shift->work_end_time);

            if ($end->lt($start)) {
                $end->addDay();
            }

            $shiftEndDate = $end->isSameDay($start)
                ? $shift->shift_date
                : Carbon::parse($shift->shift_date)->addDay();

            $waybill->start_date = min($waybill->start_date, $shift->shift_date);
            $waybill->end_date = max($waybill->end_date, $shiftEndDate);

            $waybill->saveQuietly();

            Log::debug('Waybill dates updated from shift', [
                'waybill_id' => $waybill->id,
                'new_start_date' => $waybill->start_date,
                'new_end_date' => $waybill->end_date,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in WaybillShiftObserver saving method', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
