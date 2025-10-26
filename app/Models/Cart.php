<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Cart extends Model
{
    const TYPE_REGULAR = 'regular';
    const TYPE_PROPOSAL = 'proposal';

    protected $fillable = [
        'user_id',
        'type',
        'rental_request_id',
        'total_base_amount',
        'total_platform_fee',
        'discount_amount',
        'start_date',
        'end_date',
        'reserved_until',
        'reservation_token',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'reserved_until' => 'datetime',
        'is_reservation_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function proposalItems(): HasMany
    {
        return $this->items()->where('is_proposal_item', true);
    }

    public function regularItems(): HasMany
    {
        return $this->items()->where('is_proposal_item', false);
    }

    /**
     * Проверка активного резервирования
     */
    public function getIsReservationActiveAttribute(): bool
    {
        return $this->reserved_until && $this->reserved_until->isFuture();
    }

    /**
     * Получение корзины по типу
     */
    public static function getByType(int $userId, string $type = self::TYPE_REGULAR): self
    {
        // Сначала ищем корзину с элементами
        $cart = self::where('user_id', $userId)
            ->where('type', $type)
            ->whereHas('items') // Только корзины с элементами
            ->orderBy('reserved_until', 'desc') // Сначала с активным резервированием
            ->orderBy('created_at', 'desc') // Затем самые новые
            ->first();

        // Если не нашли корзину с элементами, ищем любую
        if (!$cart) {
            $cart = self::where('user_id', $userId)
                ->where('type', $type)
                ->orderBy('reserved_until', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // Если все еще не нашли, создаем новую
        if (!$cart) {
            $cart = self::create([
                'user_id' => $userId,
                'type' => $type,
            ]);
        }

        return $cart;
    }

    /**
     * Создание корзины для подтвержденного предложения
     */
    public static function createFromProposal(RentalRequestResponse $proposal, User $user): self
    {
        // Вместо создания новой корзины, используем существующую
        $cart = self::getByType($user->id, self::TYPE_PROPOSAL);

        return $cart->update([
            'rental_request_id' => $proposal->rental_request_id,
            'reserved_until' => now()->addHours(24),
            'reservation_token' => Str::uuid(),
        ]) ? $cart : $cart;
    }

    /**
     * Продление резервирования
     */
    public function extendReservation(): bool
    {
        if (!$this->is_reservation_active) {
            return false;
        }

        return $this->update([
            'reserved_until' => now()->addHours(24),
        ]);
    }

    /**
     * Пересчитывает итоговые суммы корзины
     */
    public function recalculateTotals(): void
    {
        $this->total_base_amount = $this->items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $this->total_platform_fee = $this->items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        $this->discount_amount = 0;
        $this->save();
    }
}
