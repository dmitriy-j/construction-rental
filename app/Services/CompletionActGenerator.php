<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CompletionAct;
use App\Models\Waybill;
use App\Models\Contract;

class CompletionActGenerator
{
    public static function generate(Order $order)
    {
        // Явно загружаем ВСЕ необходимые отношения
        $order->load([
            'waybills',
            'contract',
            'items' // Добавлена загрузка items
        ]);

        // Проверяем наличие путевых листов
        if ($order->waybills->isEmpty()) {
            throw new \Exception('No waybills found for order #' . $order->id);
        }

        $waybills = $order->waybills;
        $contract = $order->contract;

        // Находим последнюю дату работ
        $lastWorkDate = $waybills->max('work_date');

          // Добавьте проверку
        if (!$lastWorkDate) {
            throw new \Exception('No valid work_date found in waybills');
        }

        $act = new CompletionAct([
            'order_id' => $order->id,
            'act_date' => now(),
            'service_start_date' => $order->service_start_date, // Добавлено
            'service_end_date' => $lastWorkDate,
            'total_hours' => $waybills->sum('hours_worked'),
            'total_downtime' => $waybills->sum('downtime_hours'),
            'prepayment_amount' => $order->prepayment_amount ?? 0, // Исправлено
        ]);

        if (!$order->service_start_date) {
        throw new \Exception('Service start date not set for order #' . $order->id);
        }

        // Исправленный расчет штрафов
        $act->penalty_amount = self::calculatePenalties($waybills, $contract, $order);

        // Итоговая сумма с учетом базовой стоимости
        // Добавлена проверка на наличие items
        $hourlyRate = $order->items->first() ? $order->items->first()->price_per_unit : 0;
        $baseAmount = $act->total_hours * $hourlyRate;

        $act->total_amount = $baseAmount + $act->penalty_amount;
        $act->final_amount = $act->total_amount - $act->prepayment_amount;

        $act->save();
        return $act;
    }

    private static function calculatePenalties($waybills, $contract, $order)
    {
        $penalty = 0;
        $hourlyRate = $order->items->first() ? $order->items->first()->price_per_unit : 0;

        foreach ($waybills as $waybill) {
            if ($waybill->downtime_cause === 'lessee') {
                // Исправленная формула (убрано деление на 100)
                $penalty += $waybill->downtime_hours
                            * $contract->penalty_rate
                            * $hourlyRate;
            }
        }

        return $penalty;
    }
}
