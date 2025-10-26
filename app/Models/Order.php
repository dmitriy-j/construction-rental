<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB; // Добавлено
use Illuminate\Support\Facades\Log; // Добавлено

class Order extends Model
{
    use HasFactory, SoftDeletes;

    // Константы статусов
    const STATUS_PENDING = 'pending';

    const STATUS_PENDING_APPROVAL = 'pending_approval'; // Новый статус

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_EXTENSION_REQUESTED = 'extension_requested';

    const STATUS_REJECTED = 'rejected'; // Новый статус

    const STATUS_AGGREGATED = 'aggregated'; // Добавляем новый статус

    const DELIVERY_PICKUP = 'pickup';

    const DELIVERY_DELIVERY = 'delivery';

    const STATUS_IN_DELIVERY = 'in_delivery';

    protected $fillable = [
        'lessee_company_id',
        'lessor_company_id',
        'user_id',
        'status',
        'total_amount',
        'notes',
        'start_date',
        'end_date',
        'service_start_date',
        'service_end_date',
        'contract_date',
        'extension_requested',
        'requested_end_date',
        'platform_id',
        'base_amount',
        'platform_fee',
        'delivery_cost',
        'discount_amount',
        'lessor_payout',
        'prepayment_amount',
        'penalty_amount',
        'contract_id',
        'rejection_reason', // Новое поле
        'confirmed_at', // Новое поле
        'rejected_at', // Новое поле
        'rental_condition_id', // Новое поле
        'shift_hours',
        'shifts_per_day',
        'payment_type',
        'transportation',
        'fuel_responsibility',
        'lessor_base_amount',
        'delivery_type',
        'company_order_number',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'requested_end_date' => 'datetime',
        'contract_date' => 'datetime',
        'extension_requested' => 'boolean',
        'prepayment_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'delivery_cost' => 'float',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_CONFIRMED,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_EXTENSION_REQUESTED,
            self::STATUS_REJECTED,
            self::STATUS_AGGREGATED,
            self::STATUS_IN_DELIVERY, // Добавляем новый
        ];
    }

    public static function statusText(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Ожидает обработки',
            self::STATUS_PENDING_APPROVAL => 'Ожидает подтверждения арендодателем',
            self::STATUS_CONFIRMED => 'Подтвержден',
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_COMPLETED => 'Завершен',
            self::STATUS_CANCELLED => 'Отменен',
            self::STATUS_EXTENSION_REQUESTED => 'Запрос продления',
            self::STATUS_REJECTED => 'Отклонен',
            self::STATUS_AGGREGATED => 'Агрегированный заказ',
            self::STATUS_IN_DELIVERY => 'В пути',
            default => $status,
        };
    }

    public function lesseeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessee_company_id');
    }

    public function lessorCompany()
    {
        return $this->belongsTo(Company::class, 'lessor_company_id')->withDefault([
            'legal_name' => 'Компания недоступна',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function canGenerateCompletionAct(): bool
    {
        return in_array($this->status, ['active', 'completed'])
            && $this->service_start_date
            && $this->waybills()->exists()
            && ! $this->completionAct;
    }

    public function deliveryNote(): HasOneThrough // Изменен тип возвращаемого значения
    {
        return $this->hasOneThrough(
            DeliveryNote::class,
            OrderItem::class,
            'order_id', // Внешний ключ в order_items
            'order_item_id', // Внешний ключ в delivery_notes
            'id', // Локальный ключ в orders
            'id' // Локальный ключ в order_items
        );
    }

    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class);
    }

    public function waybills(): HasMany
    {
        return $this->hasMany(Waybill::class);
    }

    public function completionAct(): HasOne
    {
        return $this->hasOne(CompletionAct::class);
    }

    public function setServiceStartDate($date): void
    {
        $this->service_start_date = Carbon::parse($date);
        $this->save();
        $this->refresh();
    }

    public function cancel()
    {
        $allowedStatuses = [
            self::STATUS_PENDING,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_CONFIRMED,
            self::STATUS_AGGREGATED,
        ];

        if (! in_array($this->status, $allowedStatuses)) {
            throw new \Exception('Невозможно отменить заказ в текущем статусе');
        }

        DB::beginTransaction();

        try {
            $this->status = self::STATUS_CANCELLED;
            $this->save();

            // Отменяем дочерние заказы
            if ($this->isParent()) {
                foreach ($this->childOrders as $childOrder) {
                    $childOrder->status = self::STATUS_CANCELLED;
                    $childOrder->save();
                }
            }

            // Освобождаем оборудование с отметкой "отменено"
            app(\App\Services\EquipmentAvailabilityService::class)->releaseBooking($this);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при отмене заказа: '.$e->getMessage());
            throw $e;
        }

        return $this;
    }

    public function getStatusColorAttribute(): string
    {
        // Для агрегированных заказов используем особый цвет
        if ($this->status === self::STATUS_AGGREGATED) {
            return 'info';
        }

        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PENDING_APPROVAL => 'secondary',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_ACTIVE => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_EXTENSION_REQUESTED => 'secondary',
            self::STATUS_REJECTED => 'dark',
            default => 'light',
        };
    }

    public function getAggregatedStatusAttribute()
    {
        if ($this->isChild()) {
            return $this->status;
        }

        $statuses = $this->childOrders->pluck('status')->unique();

        // Проверяем статусы доставки
        if ($statuses->contains(Order::STATUS_IN_DELIVERY)) {
            if ($statuses->every(fn ($s) => $s === Order::STATUS_IN_DELIVERY)) {
                return 'in_delivery';
            }

            return 'partially_in_delivery';
        }

        if ($statuses->contains(Order::STATUS_CANCELLED)) {
            return 'partially_cancelled';
        }

        if ($statuses->contains(Order::STATUS_REJECTED)) {
            return 'partially_rejected';
        }

        if ($statuses->every(fn ($s) => $s === Order::STATUS_COMPLETED)) {
            return 'completed';
        }

        if ($statuses->every(fn ($s) => $s === Order::STATUS_ACTIVE)) {
            return 'active';
        }

        return 'processing';
    }

    public function getStatusTextAttribute(): string
    {
        return self::statusText($this->status);
    }

    public function getDeliveryCostAttribute()
    {
        // Если значение уже установлено - используем его
        if (isset($this->attributes['delivery_cost'])) {
            return $this->attributes['delivery_cost'];
        }

        // Для родительских заказов - суммируем дочерние
        if ($this->isParent()) {
            return $this->childOrders->sum('delivery_cost');
        }

        // Для дочерних заказов - суммируем позиции
        return $this->items->sum('delivery_cost');
    }

    public function belongsToLessee(User $user): bool
    {
        return $this->lessee_company_id === $user->company_id;
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 2).' ₽';
    }

    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function childOrders()
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }

    public function isParent()
    {
        return is_null($this->parent_order_id);
    }

    public function isChild()
    {
        return ! is_null($this->parent_order_id);
    }

    public function getTotalItemsCountAttribute()
    {
        if ($this->isParent()) {
            return $this->childOrders->sum(function ($childOrder) {
                return $childOrder->items->count();
            });
        }

        return $this->items->count();
    }

    public function requestResponse()
    {
        return $this->belongsTo(RentalRequestResponse::class);
    }

    public function getBaseAmountAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->base_price * $item->quantity;
        });
    }

    public function getFormattedBaseAmountAttribute(): string
    {
        return number_format($this->base_amount, 2).' ₽';
    }

    public function getLessorPayoutAttribute()
    {
        return $this->lessor_base_amount
            + $this->delivery_cost
            - $this->discount_amount;
    }

    public function getLesseeTotalAttribute()
    {
        return $this->base_amount
            + $this->delivery_cost
            - $this->discount_amount;
    }

    public function getTotalPayoutAttribute()
    {
        return $this->lessor_base_amount + $this->delivery_cost;
    }

    public function canBeActivated(): bool
    {
        // Проверка статуса
        $allowedStatuses = [self::STATUS_CONFIRMED, self::STATUS_IN_DELIVERY];
        if (! in_array($this->status, $allowedStatuses)) {
            return false;
        }

        // Проверка даты
        if (now()->lt($this->start_date)) {
            return false;
        }

        // Проверка операторов
        foreach ($this->items as $item) {
            if (! $item->equipment->hasActiveDayOperator()) {
                return false;
            }
            if ($this->rentalCondition->shifts_per_day > 1 &&
                ! $item->equipment->hasActiveNightOperator()) {
                return false;
            }
        }

        return true;
    }

    public function activationAvailableDate(): string
    {
        return $this->start_date->format('d.m.Y');
    }

    public function getActivationErrors(): array
    {
        $errors = [];

        // Проверка статуса
        $allowedStatuses = [self::STATUS_CONFIRMED, self::STATUS_IN_DELIVERY];
        if (! in_array($this->status, $allowedStatuses)) {
            $errors[] = 'Невозможно начать аренду в текущем статусе: '.$this->status_text;
        }

        // Проверка даты
        if (now()->lt($this->start_date)) {
            $errors[] = 'Нельзя начать аренду раньше '.$this->start_date->format('d.m.Y');
        }

        // Проверка операторов
        $rentalCondition = $this->rentalCondition;
        $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;

        foreach ($this->items as $item) {
            $equipment = $item->equipment;

            if (! $equipment->hasActiveDayOperator()) {
                $errors[] = "Для {$equipment->title} не назначен активный дневной оператор";
            }

            if ($shiftsPerDay > 1 && ! $equipment->hasActiveNightOperator()) {
                $errors[] = "Для {$equipment->title} не назначен активный ночной оператор";
            }
        }

        return $errors;
    }

    public function deliveryLocation()
    {
        return $this->belongsTo(Location::class, 'delivery_location_id');
    }

    public function deliveryFrom()
    {
        return $this->belongsTo(Location::class, 'delivery_from_id');
    }

    public function deliveryTo()
    {
        return $this->belongsTo(Location::class, 'delivery_to_id');
    }

    public function checkOrderStructure(Order $order)
    {
        if ($order->isParent()) {
            if ($order->lessor_company_id) {
                throw new \Exception('Родительский заказ не должен иметь lessor_company_id');
            }

            if ($order->childOrders->isEmpty()) {
                throw new \Exception('Родительский заказ без дочерних заказов');
            }
        } else {
            if (! $order->lessor_company_id) {
                throw new \Exception('Дочерний заказ без lessor_company_id');
            }

            if (! $order->rental_condition_id) {
                throw new \Exception('Дочерний заказ без rental_condition_id');
            }
        }
    }

    public function getDeliveryScenarioAttribute()
    {
        if ($this->delivery_type === Order::DELIVERY_PICKUP) {
            return 'none';
        }

        $firstNote = $this->items->first()->deliveryNote ?? null;

        return $firstNote ? $firstNote->delivery_scenario : 'none';
    }

    public function getDeliveryTypeAttribute($value)
    {
        \Log::debug('Order delivery_type accessed', [
            'order_id' => $this->id,
            'value' => $value,
            'is_parent' => $this->isParent(),
            'is_child' => $this->isChild(),
        ]);

        return $value;
    }

    public function updateAggregateStatus()
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return;
        }

        // Все ли позиции в доставке?
        if ($items->every(fn ($i) => $i->status === OrderItem::STATUS_IN_DELIVERY)) {
            $this->status = self::STATUS_IN_DELIVERY;
        }
        // Все ли позиции активны?
        elseif ($items->every(fn ($i) => $i->status === OrderItem::STATUS_ACTIVE)) {
            $this->status = self::STATUS_ACTIVE;
        }
        // Все ли позиции завершены?
        elseif ($items->every(fn ($i) => $i->status === OrderItem::STATUS_COMPLETED)) {
            $this->status = self::STATUS_COMPLETED;
        }
        // Смешанные статусы
        else {
            $this->status = self::STATUS_AGGREGATED;
        }

        $this->save();
    }

    public function updateStatusBasedOnItems()
    {
        // Для родительских заказов проверяем дочерние элементы
        $allItems = $this->isParent()
            ? $this->childOrders->flatMap->items
            : $this->items;

        if ($allItems->isEmpty()) {
            \Log::debug('No items found for order status update', ['order_id' => $this->id]);

            return;
        }

        $originalStatus = $this->status;
        $itemStatuses = $allItems->pluck('status')->unique()->toArray();

        // Логируем текущие статусы позиций
        \Log::debug('Order item statuses', [
            'order_id' => $this->id,
            'statuses' => $itemStatuses,
        ]);

        // Определяем новый статус заказа
        if ($allItems->every(fn ($i) => $i->status === OrderItem::STATUS_IN_DELIVERY)) {
            $newStatus = self::STATUS_IN_DELIVERY;
        } elseif ($allItems->every(fn ($i) => $i->status === OrderItem::STATUS_ACTIVE)) {
            $newStatus = self::STATUS_ACTIVE;
        } elseif ($allItems->every(fn ($i) => $i->status === OrderItem::STATUS_COMPLETED)) {
            $newStatus = self::STATUS_COMPLETED;
        } else {
            $newStatus = $this->status;
        }

        // Обновляем статус при изменении
        if ($newStatus !== $this->status) {
            $this->status = $newStatus;
            $this->save();

            \Log::info('Order status updated', [
                'order_id' => $this->id,
                'from' => $originalStatus,
                'to' => $newStatus,
                'reason' => 'item_status_change',
            ]);
        }
    }

    protected function getNextCompanyOrderNumber($companyId)
    {
        $lastOrder = Order::where('lessee_company_id', $companyId)
            ->orWhere('lessor_company_id', $companyId)
            ->orderBy('company_order_number', 'desc')
            ->first();

        return ($lastOrder->company_order_number ?? 0) + 1;
    }

    public function scopeNextCompanyOrderNumber($query, $companyId)
    {
        $lastOrder = $query->where(function ($q) use ($companyId) {
            $q->where('lessee_company_id', $companyId)
                ->orWhere('lessor_company_id', $companyId);
        })
            ->orderBy('company_order_number', 'desc')
            ->first();

        return ($lastOrder->company_order_number ?? 0) + 1;
    }

    public function getRentalDaysAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}
