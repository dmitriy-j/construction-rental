<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'equipment_id',
        'rental_term_id',
        'rental_condition_id',
        'period_count',
        'actual_working_hours',
        'base_price',
        'total_price',
        'fixed_lessor_price',
        'fixed_customer_price',
        'platform_fee',
        'start_date',
        'end_date',
        'shifts_per_day',
        'hours_per_shift',
        'quantity',
        'address',
        'delivery_from_id',
        'delivery_to_id',
        'delivery_cost',
        'distance_km',
        'delivery_cost_calculated',
        'proposal_id',
        'rental_request_item_id',
        'is_proposal_item',
        'proposal_data',
        'actual_working_hours',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_proposal_item' => 'boolean',
        'proposal_data' => 'array',
        'shifts_per_day' => 'integer',
        'hours_per_shift' => 'integer',
        'quantity' => 'integer',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(EquipmentRentalTerm::class, 'rental_term_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(RentalRequestResponse::class, 'proposal_id');
    }

    public function rentalRequestItem(): BelongsTo
    {
        return $this->belongsTo(RentalRequestItem::class, 'rental_request_item_id');
    }

    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class, 'rental_condition_id');
    }

    public function deliveryFrom(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_from_id');
    }

    public function deliveryTo(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_to_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    /**
     * Аксессор для total_price — вычисляет, если поле не заполнено
     */
    public function getTotalPriceAttribute($value)
    {
        if ($value !== null) {
            return (float) $value;
        }

        $base = (float) ($this->base_price ?? 0);
        $fee = (float) ($this->platform_fee ?? 0);
        $period = (int) ($this->period_count ?? 1);
        $qty = (int) ($this->quantity ?? 1);

        return ($base + $fee) * $period * $qty;
    }

    /**
     * Получить отображаемую цену (аксессор для JSON)
     */
    public function getDisplayTotalAttribute(): float
    {
        return $this->total_price;
    }

    /**
     * 🔥 СТАТИЧЕСКИЙ метод для получения условий аренды для предложения
     */
    public static function getStaticRentalConditionForProposal(RentalRequestResponse $proposal, $rentalRequest)
    {
        try {
            \Log::info('🔄 CartItem::getStaticRentalConditionForProposal() called', [
                'proposal_id' => $proposal->id,
                'equipment_id' => $proposal->equipment_id
            ]);

            // Способ 1: Через оборудование в предложении
            if ($proposal->equipment && $proposal->equipment->rentalTerms) {
                $rentalTerm = $proposal->equipment->rentalTerms->first();
                if ($rentalTerm) {
                    \Log::info('✅ Found rental term via equipment', ['rental_term_id' => $rentalTerm->id]);

                    if ($rentalTerm instanceof \App\Models\RentalCondition) {
                        return $rentalTerm;
                    } else {
                        \Log::warning('⚠️ Found rental term but it is not RentalCondition', [
                            'type' => get_class($rentalTerm),
                            'id' => $rentalTerm->id
                        ]);
                    }
                }
            }

            // Способ 2: Создаем временные условия аренды на основе заявки
            if ($rentalRequest && $rentalRequest->rental_conditions) {
                $conditions = is_string($rentalRequest->rental_conditions)
                    ? json_decode($rentalRequest->rental_conditions, true)
                    : $rentalRequest->rental_conditions;

                $tempCondition = (object)[
                    'shift_hours' => $conditions['hours_per_shift'] ?? 8,
                    'shifts_per_day' => $conditions['shifts_per_day'] ?? 1,
                    'transportation' => $conditions['transportation'] ?? 'lessee',
                    'fuel_responsibility' => $conditions['fuel_responsibility'] ?? 'lessee',
                    'extension_policy' => $conditions['extension_policy'] ?? 'allowed',
                    'payment_type' => $conditions['payment_type'] ?? 'hourly'
                ];

                \Log::info('✅ Created temporary rental condition from request (no ID)', [
                    'shift_hours' => $tempCondition->shift_hours,
                    'shifts_per_day' => $tempCondition->shifts_per_day
                ]);

                return $tempCondition;
            }

            \Log::warning('❌ No rental condition found, using defaults (no ID)');

            return (object)[
                'shift_hours' => 8,
                'shifts_per_day' => 1,
                'transportation' => 'lessee',
                'fuel_responsibility' => 'lessee',
                'extension_policy' => 'allowed',
                'payment_type' => 'hourly'
            ];

        } catch (\Exception $e) {
            \Log::error('❌ Error getting rental condition for proposal: ' . $e->getMessage());

            return (object)[
                'shift_hours' => 8,
                'shifts_per_day' => 1,
                'transportation' => 'lessee',
                'fuel_responsibility' => 'lessee',
                'extension_policy' => 'allowed',
                'payment_type' => 'hourly'
            ];
        }
    }

    /**
     * 🔥 СТАТИЧЕСКИЙ метод для расчета фактических рабочих часов
     */
    public static function calculateActualWorkingHours($rentalRequest, $rentalCondition = null): int
    {
        try {
            \Log::info('🔄 CartItem::calculateActualWorkingHours() called statically', [
                'rental_request_id' => $rentalRequest->id,
                'start_date' => $rentalRequest->rental_period_start,
                'end_date' => $rentalRequest->rental_period_end
            ]);

            $start = Carbon::parse($rentalRequest->rental_period_start);
            $end = Carbon::parse($rentalRequest->rental_period_end);
            $days = $start->diffInDays($end) + 1;

            $shiftHours = 8;
            $shiftsPerDay = 1;

            if ($rentalCondition) {
                $shiftHours = $rentalCondition->shift_hours ?? 8;
                $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;
            } elseif ($rentalRequest->rental_conditions) {
                $conditions = is_string($rentalRequest->rental_conditions)
                    ? json_decode($rentalRequest->rental_conditions, true)
                    : $rentalRequest->rental_conditions;

                $shiftHours = $conditions['hours_per_shift'] ?? 8;
                $shiftsPerDay = $conditions['shifts_per_day'] ?? 1;
            }

            $workingHours = $days * $shiftHours * $shiftsPerDay;

            \Log::info('✅ Calculated actual working hours', [
                'days' => $days,
                'shift_hours' => $shiftHours,
                'shifts_per_day' => $shiftsPerDay,
                'total_hours' => $workingHours
            ]);

            return $workingHours;

        } catch (\Exception $e) {
            \Log::error('❌ Error calculating working hours: ' . $e->getMessage());

            $start = Carbon::parse($rentalRequest->rental_period_start);
            $end = Carbon::parse($rentalRequest->rental_period_end);
            $days = $start->diffInDays($end) + 1;

            return $days * 8;
        }
    }

    /**
     * Создание элемента корзины из предложения
     */
    public static function createFromProposal(RentalRequestResponse $proposal, Cart $cart): CartItem
    {
        \Log::info('🔄 CartItem::createFromProposal() called', [
            'proposal_id' => $proposal->id,
            'cart_id' => $cart->id
        ]);

        $rentalCondition = static::getStaticRentalConditionForProposal($proposal, $proposal->rentalRequest);
        $workingHours = static::calculateActualWorkingHours($proposal->rentalRequest, $rentalCondition);

        $priceBreakdown = $proposal->price_breakdown;
        if (is_string($priceBreakdown)) {
            $priceBreakdown = json_decode($priceBreakdown, true);
        }

        $customerPricePerHour = $priceBreakdown['customer_price_per_unit'] ?? $proposal->proposed_price;
        $lessorPricePerHour = $priceBreakdown['lessor_price_per_unit'] ?? ($proposal->proposed_price * 0.8);
        $platformFeePerHour = $customerPricePerHour - $lessorPricePerHour;

        $rentalConditionId = null;
        if ($rentalCondition && isset($rentalCondition->id)) {
            $existingCondition = \App\Models\RentalCondition::find($rentalCondition->id);
            if ($existingCondition) {
                $rentalConditionId = $rentalCondition->id;
            }
        }

        $totalPrice = $customerPricePerHour * $workingHours;

        $cartItem = new static([
            'cart_id' => $cart->id,
            'proposal_id' => $proposal->id,
            'equipment_id' => $proposal->equipment_id,
            'rental_term_id' => $proposal->equipment->rentalTerms->first()->id ?? null,
            'rental_condition_id' => $rentalConditionId,
            'is_proposal_item' => true,
            'start_date' => $proposal->rentalRequest->rental_period_start,
            'end_date' => $proposal->rentalRequest->rental_period_end,
            'period_count' => $workingHours,
            'actual_working_hours' => $workingHours,
            'base_price' => $customerPricePerHour,
            'total_price' => $totalPrice,
            'fixed_customer_price' => $customerPricePerHour,
            'fixed_lessor_price' => $lessorPricePerHour,
            'platform_fee' => $platformFeePerHour,
            'quantity' => $proposal->proposed_quantity ?? 1,
            'proposal_data' => [
                'original_proposal_id' => $proposal->id,
                'proposed_price' => $proposal->proposed_price,
                'price_breakdown' => $priceBreakdown,
                'calculated_at' => now()->toDateTimeString()
            ]
        ]);

        $cartItem->save();

        \Log::info('✅ CartItem created from proposal', [
            'cart_item_id' => $cartItem->id,
            'working_hours' => $workingHours,
            'total_price' => $totalPrice,
            'rental_condition_id' => $rentalConditionId
        ]);

        return $cartItem;
    }

    public function getTotalAttribute(): float
    {
        return $this->total_price;
    }
}
