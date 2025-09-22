<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UpdLessorExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected Order $order) {}

    public function query()
    {
        return $this->order->items()->with('equipment');
    }

    public function headings(): array
    {
        return [
            'Техника',
            'Тип периода',
            'Цена за единицу',
            'Количество',
            'Сумма',
        ];
    }

    public function map($item): array
    {
        return [
            $item->equipment->name,
            $item->rentalPeriod->name,
            $item->base_price,
            $item->period_count,
            $item->base_price * $item->period_count,
        ];
    }
}
