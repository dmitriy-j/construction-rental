<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletionAct extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'waybill_id', // Добавляем связь с путевым листом
        'act_date',
        'service_start_date',
        'service_end_date',
        'total_hours',
        'total_downtime',
        'penalty_amount',
        'total_amount',
        'prepayment_amount',
        'final_amount',
        'act_file_path',
        'status',
        'hourly_rate', // Добавляем из схемы
        'notes',       // Добавляем из схемы
        'document_path' // Добавляем из схемы
    ];

    protected $casts = [
        'act_date' => 'datetime',
        'service_start_date' => 'datetime',
        'service_end_date' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function waybill()
    {
        return $this->belongsTo(Waybill::class);
    }

    // Метод для создания акта из путевого листа
    public static function createFromWaybill(Waybill $waybill)
    {
        $order = $waybill->order;

        return self::create([
            'order_id' => $order->id,
            'waybill_id' => $waybill->id,
            'act_date' => now(),
            'service_start_date' => $waybill->start_date,
            'service_end_date' => $waybill->end_date,
            'total_hours' => $waybill->shifts->sum('hours_worked'),
            'total_downtime' => $waybill->shifts->sum('downtime_hours'),
            'hourly_rate' => $waybill->hourly_rate,
            'total_amount' => $waybill->shifts->sum('total_amount'),
            'status' => 'draft'
        ]);
    }
}
