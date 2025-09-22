<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletionAct extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
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
        'document_path', // Добавляем из схемы
        'perspective',
        'related_completion_act_id',
        'parent_order_id',
    ];

    protected $casts = [
        'act_date' => 'datetime',
        'service_start_date' => 'datetime',
        'service_end_date' => 'datetime',
    ];

    public function relatedCompletionAct()
    {
        return $this->belongsTo(CompletionAct::class, 'related_completion_act_id');
    }

    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function upd()
    {
        return $this->belongsTo(Upd::class);
    }

    public function scopeForLessor($query)
    {
        return $query->where('perspective', 'lessor');
    }

    public function waybill()
    {
        return $this->belongsTo(Waybill::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Генерация номера в зависимости от перспективы
            if (empty($model->number)) {
                if ($model->perspective === 'lessor') {
                    $prefix = 'АКТ-';
                    $count = static::where('perspective', 'lessor')->count() + 1;
                } else {
                    $prefix = 'АКТ-АР-';
                    $count = static::where('perspective', 'lessee')->count() + 1;
                }

                $model->number = $prefix.date('Ymd').'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
            }

            // Автоматическая установка parent_order_id, если не задан
            if (empty($model->parent_order_id) && ! empty($model->order_id)) {
                $order = Order::find($model->order_id);
                if ($order && $order->parent_order_id) {
                    $model->parent_order_id = $order->parent_order_id;
                }
            }

            // Установка даты акта, если не задана
            if (empty($model->act_date)) {
                $model->act_date = now();
            }
        });

        static::created(function ($model) {
            // После создания акта, отправляем уведомление, если это акт для арендатора
            if ($model->perspective === 'lessee' && $model->parentOrder) {
                $usersToNotify = \App\Models\User::where('company_id', $model->parentOrder->lessee_company_id)
                    ->whereHas('roles', function ($query) {
                        $query->whereIn('name', ['company_admin', 'company_user']);
                    })
                    ->get();

                foreach ($usersToNotify as $user) {
                    $user->notify(
                        new \App\Notifications\NewDocumentAvailable($model, 'акт выполненных работ')
                    );
                }
            }
        });

        static::updating(function ($model) {
            // Запрещаем изменение номера после создания
            if ($model->isDirty('number') && ! is_null($model->getOriginal('number'))) {
                throw new \Exception('Номер акта нельзя изменять после создания');
            }

            // Запрещаем изменение перспективы после создания
            if ($model->isDirty('perspective') && ! is_null($model->getOriginal('perspective'))) {
                throw new \Exception('Перспективу акта нельзя изменять после создания');
            }
        });
    }

    // Метод для создания акта из путевого листа
    public static function createFromWaybill(Waybill $waybill)
    {
        // Проверяем, нет ли уже акта для этого путевого листа
        $existingAct = self::where('waybill_id', $waybill->id)->first();

        if ($existingAct) {
            \Log::warning('Попытка создать дублирующий акт для путевого листа', [
                'waybill_id' => $waybill->id,
                'existing_act_id' => $existingAct->id,
            ]);

            return $existingAct;
        }

        // Проверяем, нет ли у путевого листа уже УПД
        if ($waybill->upd_id) {
            throw new \Exception('Для путевого листа уже создан УПД, нельзя создать акт');
        }

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
            'status' => 'draft',
            'perspective' => 'lessor', // Сначала создаем для арендодателя
        ]);
    }

    public function forLessor()
    {
        return [
            'id' => $this->id,
            'act_date' => $this->act_date->format('d.m.Y'),
            'service_period' => $this->service_start_date->format('d.m.Y').' - '.$this->service_end_date->format('d.m.Y'),
            'total_hours' => $this->total_hours,
            'hourly_rate' => $this->hourly_rate,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'perspective' => 'lessor',
        ];
    }

    public function forLessee()
    {
        $order = $this->order;
        $customerHourlyRate = $order->items->first()->price_per_unit;
        $totalAmount = $this->total_hours * $customerHourlyRate;

        return [
            'id' => $this->id,
            'act_date' => $this->act_date->format('d.m.Y'),
            'service_period' => $this->service_start_date->format('d.m.Y').' - '.$this->service_end_date->format('d.m.Y'),
            'total_hours' => $this->total_hours,
            'hourly_rate' => $customerHourlyRate,
            'total_amount' => $totalAmount,
            'status' => $this->status,
            'perspective' => 'lessee',
            'lessor_name' => 'Платформа', // Заменяем на нейтральное название
            'order_id' => $this->order_id,
            'parent_order_id' => $this->parent_order_id,
            'created_at' => $this->created_at,
        ];
    }

    public function waybillWithLock()
    {
        return $this->belongsTo(Waybill::class)->lockForUpdate();
    }
}
