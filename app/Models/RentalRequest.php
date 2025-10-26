<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;


class RentalRequest extends Model
{
    use HasFactory;

      protected $fillable = [
        'user_id', 'company_id', 'title', 'description',
        'total_equipment_quantity', // ИСПРАВЛЕНО: было equipment_quantity
        'hourly_rate', 'total_budget', 'calculated_budget_from', 'calculated_budget_to', // ДОБАВЛЕНО total_budget
        'rental_conditions', 'desired_specifications', 'rental_period_start', 'rental_period_end',
        'budget_from', 'budget_to', 'location_id', 'delivery_required', 'status', 'expires_at'
    ];

     protected $casts = [
        'desired_specifications' => 'array',
        'rental_conditions' => 'array',
        'rental_period_start' => 'date',
        'rental_period_end' => 'date',
        'budget_from' => 'decimal:2',
        'budget_to' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_budget' => 'decimal:2', // ДОБАВЛЕНО
        'calculated_budget_from' => 'decimal:2',
        'calculated_budget_to' => 'decimal:2',
        'delivery_required' => 'boolean',
        'expires_at' => 'datetime',
        'visibility' => 'string',
        'max_hourly_rate' => 'decimal:2',
    ];

    // ОБНОВИТЬ перечень статусов
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused'; // ДОБАВЛЕНО
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public')
                    ->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    public function scopeVisibleToLessor($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public')
            ->orWhere(function ($q2) use ($user) {
                // Для приватных заявок - проверяем доступ арендодателя
                $q2->where('visibility', 'private')
                    ->whereIn('category_id', $user->company->equipment()->pluck('category_id'));
            });
        });
    }

    public function getPublicProposalsAttribute()
    {
        return $this->responses()
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->with('lessor.company')
                    ->get()
                    ->makeHidden(['price_breakdown', 'lessor_id']); // Скрываем конфиденциальные данные
    }

    public function calculateClientSaving($proposedPrice)
    {
        $maxBudget = $this->max_hourly_rate ?? $this->hourly_rate;
        return max(0, $maxBudget - $proposedPrice);
    }

    /**
     * Расчет бюджета на основе условий аренды
     */
    public function calculateBudget(): void
    {
        \Log::info('Starting budget calculation', ['request_id' => $this->id]);

        // Принудительно загружаем items если еще не загружены
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }

        if ($this->items->isEmpty()) {
            \Log::warning('No items found for budget calculation', ['request_id' => $this->id]);
            $this->total_budget = 0;
            $this->calculated_budget_from = 0;
            $this->calculated_budget_to = 0;
            $this->total_equipment_quantity = 0;
            $this->save();
            return;
        }

        $totalBudget = 0;
        $totalQuantity = 0;

        \Log::debug('Calculating budget for items', [
            'items_count' => $this->items->count(),
            'request_id' => $this->id
        ]);

        // Расчет для каждой позиции с сохранением
        foreach ($this->items as $item) {
            $itemPrice = $item->calculateItemPrice();

            \Log::debug('Item price calculated', [
                'item_id' => $item->id,
                'price' => $itemPrice,
                'quantity' => $item->quantity
            ]);

            // Сохраняем расчет для позиции
            if ($item->calculated_price != $itemPrice) {
                $item->calculated_price = $itemPrice;
                $item->save();
            }

            $totalBudget += $itemPrice;
            $totalQuantity += $item->quantity;
        }

        \Log::info('Budget calculation completed', [
            'request_id' => $this->id,
            'total_budget' => $totalBudget,
            'total_quantity' => $totalQuantity
        ]);

        // Сохраняем ОДНОВРЕМЕННО все поля бюджета
        $this->total_budget = $totalBudget;
        $this->calculated_budget_from = $totalBudget;
        $this->calculated_budget_to = $totalBudget;
        $this->total_equipment_quantity = $totalQuantity;

        // Явно сохраняем изменения
        $this->save();

        \Log::info('Budget saved to database', [
            'request_id' => $this->id,
            'total_budget' => $this->total_budget
        ]);
    }

    public function recalculateBudget(): void
    {
        \Log::info('Forced budget recalculation', ['request_id' => $this->id]);
        $this->calculateBudget();
        $this->refresh();
    }

    public function calculateBudgetPreview(): float
    {
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }

        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->calculateItemPrice();
        }
        return $total;
    }

    public function getDefaultRentalConditions(): array
    {
        return [
            'payment_type' => 'hourly',
            'hours_per_shift' => 8,
            'shifts_per_day' => 1,
            'transportation_organized_by' => 'lessor',
            'gsm_payment' => 'included',
            'accommodation_payment' => false,
            'extension_possibility' => true,
            'operator_included' => false,
            'minimum_rental_period' => 1
        ];
    }

    // Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function responses()
    {
        return $this->hasMany(RentalRequestResponse::class);
    }

    public function items()
    {
        return $this->hasMany(RentalRequestItem::class);
    }

    public function acceptedResponse()
    {
        return $this->hasOne(RentalRequestResponse::class)->where('status', 'accepted');
    }

    public function proposals()
    {
        return $this->hasMany(RentalRequestResponse::class)->proposals();
    }

    // 🔥 НОВОЕ ОТНОШЕНИЕ: только комментарии
    public function comments()
    {
        return $this->hasMany(RentalRequestResponse::class)->comments();
    }


    // Атрибуты
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_ACTIVE => 'Активна',
            self::STATUS_PAUSED => 'Приостановлена', // ДОБАВЛЕНО
            self::STATUS_PROCESSING => 'В обработке',
            self::STATUS_COMPLETED => 'Завершена',
            self::STATUS_CANCELLED => 'Отменена',
            default => 'Неизвестно'
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_PAUSED => 'warning', // ДОБАВЛЕНО
            self::STATUS_PROCESSING => 'warning',
            self::STATUS_COMPLETED => 'primary',
            self::STATUS_CANCELLED => 'danger',
            default => 'light'
        };
    }

    /**
     * Общее количество техники во всех позициях
     */
    public function getTotalEquipmentQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getSafeCategoryNameAttribute()
    {
        return $this->category->name ?? 'Категория не указана';
    }

    public function getItemsCountAttribute()
    {
        return $this->items->count() ?: 1;
    }
}
