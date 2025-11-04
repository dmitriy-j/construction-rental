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
        'rental_term_id',
        'period_count',
        'base_price',
        'fixed_lessor_price',
        'fixed_customer_price',
        'platform_fee',
        'start_date',
        'end_date',
        'rental_condition_id',
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
     * 🔥 СТАТИЧЕСКИЙ метод для получения условий аренды для предложения
     * ✅ ИСПРАВЛЕНО: Переименован для избежания конфликта
     */
    public static function getStaticRentalConditionForProposal(RentalRequestResponse $proposal, $rentalRequest)
    {
        try {
            \Log::info('🔄 CartItem::getStaticRentalConditionForProposal() called', [
                'proposal_id' => $proposal->id,
                'equipment_id' => $proposal->equipment_id
            ]);

            // Способ 1: Через оборудование в предложении (ПРОВЕРЯЕМ СУЩЕСТВОВАНИЕ)
            if ($proposal->equipment && $proposal->equipment->rentalTerms) {
                $rentalTerm = $proposal->equipment->rentalTerms->first();
                if ($rentalTerm) {
                    \Log::info('✅ Found rental term via equipment', ['rental_term_id' => $rentalTerm->id]);

                    // 🔥 ПРОВЕРЯЕМ, ЧТО ЭТО ДЕЙСТВИТЕЛЬНО RentalCondition, а не EquipmentRentalTerm
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

                // 🔥 СОЗДАЕМ ВРЕМЕННЫЙ ОБЪЕКТ БЕЗ ID
                $tempCondition = (object)[
                    // 🔥 НЕ УКАЗЫВАЕМ ID чтобы избежать foreign key constraint
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

            // 🔥 ВОЗВРАЩАЕМ ОБЪЕКТ БЕЗ ID
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

            // 🔥 ВОЗВРАЩАЕМ ОБЪЕКТ БЕЗ ID ПРИ ОШИБКЕ
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

            // Получаем настройки смен из условий аренды или используем значения по умолчанию
            $shiftHours = 8;
            $shiftsPerDay = 1;

            if ($rentalCondition) {
                $shiftHours = $rentalCondition->shift_hours ?? 8;
                $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;

                \Log::info('📊 Using rental condition hours', [
                    'shift_hours' => $shiftHours,
                    'shifts_per_day' => $shiftsPerDay
                ]);
            } elseif ($rentalRequest->rental_conditions) {
                $conditions = is_string($rentalRequest->rental_conditions)
                    ? json_decode($rentalRequest->rental_conditions, true)
                    : $rentalRequest->rental_conditions;

                $shiftHours = $conditions['hours_per_shift'] ?? 8;
                $shiftsPerDay = $conditions['shifts_per_day'] ?? 1;

                \Log::info('📊 Using request condition hours', [
                    'shift_hours' => $shiftHours,
                    'shifts_per_day' => $shiftsPerDay
                ]);
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

            // Fallback расчет
            $start = Carbon::parse($rentalRequest->rental_period_start);
            $end = Carbon::parse($rentalRequest->rental_period_end);
            $days = $start->diffInDays($end) + 1;

            $fallbackHours = $days * 8;
            \Log::info('🔄 Using fallback hours calculation', ['hours' => $fallbackHours]);

            return $fallbackHours;
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

        // Получаем условия аренды и рабочие часы
        $rentalCondition = static::getStaticRentalConditionForProposal($proposal, $proposal->rentalRequest);
        $workingHours = static::calculateActualWorkingHours($proposal->rentalRequest, $rentalCondition);

        // Получаем цены из breakdown или используем предложенную цену
        $priceBreakdown = $proposal->price_breakdown;
        if (is_string($priceBreakdown)) {
            $priceBreakdown = json_decode($priceBreakdown, true);
        }

        $customerPricePerHour = $priceBreakdown['customer_price_per_unit'] ?? $proposal->proposed_price;
        $lessorPricePerHour = $priceBreakdown['lessor_price_per_unit'] ?? ($proposal->proposed_price * 0.8);
        $platformFeePerHour = $customerPricePerHour - $lessorPricePerHour;

        // 🔥 ИСПРАВЛЕНИЕ: Проверяем существование rental_condition_id
        $rentalConditionId = null;
        if ($rentalCondition && isset($rentalCondition->id)) {
            // Проверяем, что запись существует в базе данных
            $existingCondition = \App\Models\RentalCondition::find($rentalCondition->id);
            if ($existingCondition) {
                $rentalConditionId = $rentalCondition->id;
            } else {
                \Log::warning('❌ Rental condition not found in database', ['rental_condition_id' => $rentalCondition->id]);
            }
        }

        \Log::info('💰 Price calculation and rental condition', [
            'proposed_price' => $proposal->proposed_price,
            'customer_price_per_hour' => $customerPricePerHour,
            'lessor_price_per_hour' => $lessorPricePerHour,
            'platform_fee_per_hour' => $platformFeePerHour,
            'rental_condition_id' => $rentalConditionId,
            'rental_term_id' => $proposal->equipment->rentalTerms->first()->id ?? null
        ]);

        // Создаем элемент корзины
        $cartItem = new static([
            'cart_id' => $cart->id,
            'proposal_id' => $proposal->id,
            'equipment_id' => $proposal->equipment_id,
            'rental_term_id' => $proposal->equipment->rentalTerms->first()->id ?? null,
            'rental_condition_id' => $rentalConditionId, // 🔥 ИСПРАВЛЕНО: Может быть null
            'is_proposal_item' => true,
            'start_date' => $proposal->rentalRequest->rental_period_start,
            'end_date' => $proposal->rentalRequest->rental_period_end,
            'period_count' => $workingHours,
            'actual_working_hours' => $workingHours,
            'base_price' => $customerPricePerHour,
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
            'rental_condition_id' => $rentalConditionId
        ]);

        return $cartItem;
    }

    /**
     * 🔥 РАСЧЕТ ЧАСОВ С УЧЕТОМ УСЛОВИЙ АРЕНДЫ
     */
    public static function calculateWorkingHoursWithConditions($start, $end, $rentalCondition): int
    {
        $shiftHours = $rentalCondition->shift_hours ?? 8;
        $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;
        $workingDays = $rentalCondition->working_days ?? [1, 2, 3, 4, 5]; // Пн-Пт по умолчанию

        $totalHours = 0;
        $current = $start->copy();

        while ($current <= $end) {
            if (in_array($current->dayOfWeek, $workingDays)) {
                $totalHours += $shiftHours * $shiftsPerDay;
            }
            $current->addDay();
        }

        return $totalHours;
    }

    /**
     * 🔥 СТАНДАРТНЫЙ РАСЧЕТ ЧАСОВ (без учета условий)
     */
    public static function calculateStandardWorkingHours($start, $end): int
    {
        $totalHours = 0;
        $current = $start->copy();

        while ($current <= $end) {
            // Исключаем субботу (6) и воскресенье (0)
            if (!in_array($current->dayOfWeek, [0, 6])) {
                $totalHours += 8; // 8 часов в рабочий день
            }
            $current->addDay();
        }

        return $totalHours;
    }

    /**
     * 🔥 НЕСТАТИЧЕСКИЙ метод для получения условий аренды
     * ✅ ИСПРАВЛЕНО: Для использования в ProposalCartController
     */
    public function getRentalConditionForProposal()
    {
        \Log::info('🔄 getRentalConditionForProposal() called for instance', ['cart_item_id' => $this->id]);

        // Способ 1: Через отношение rentalCondition
        if ($this->relationLoaded('rentalCondition') && $this->rentalCondition) {
            \Log::info('✅ Found via rentalCondition relation');
            return $this->rentalCondition;
        }

        // Способ 2: Через оборудование
        if ($this->relationLoaded('proposal.equipment.rentalTerms') &&
            $this->proposal &&
            $this->proposal->equipment &&
            $this->proposal->equipment->rentalTerms) {
            \Log::info('✅ Found via proposal equipment');
            return $this->proposal->equipment->rentalTerms->first();
        }

        // Способ 3: Через ID условия аренды
        if ($this->rental_term_id) {
            $condition = RentalCondition::find($this->rental_term_id);
            \Log::info('✅ Found via rental_term_id', ['rental_term_id' => $this->rental_term_id]);
            return $condition;
        }

        \Log::warning('❌ No rental condition found for instance');
        return null;
    }

    /**
     * ✅ НОВЫЙ МЕТОД: Расчет рабочих часов для существующего CartItem
     * ✅ ИСПРАВЛЕНО: Для использования в ProposalCartController
     */
    public function calculateWorkingHoursForCartItem(): int
    {
        try {
            \Log::info('🔄 calculateWorkingHoursForCartItem() called for instance', [
                'cart_item_id' => $this->id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date
            ]);

            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);
            $days = $start->diffInDays($end) + 1;

            // Получаем условия аренды
            $rentalCondition = $this->getRentalConditionForProposal();

            $shiftHours = $rentalCondition->shift_hours ?? 8;
            $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;

            $hours = $days * $shiftHours * $shiftsPerDay;

            \Log::info('✅ Instance hours calculated', [
                'days' => $days,
                'shift_hours' => $shiftHours,
                'shifts_per_day' => $shiftsPerDay,
                'total_hours' => $hours
            ]);

            return $hours;

        } catch (\Exception $e) {
            \Log::error('❌ Error calculating working hours for cart item: ' . $e->getMessage());

            // Fallback расчет
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);
            $days = $start->diffInDays($end) + 1;

            return $days * 8;
        }
    }

    /**
     * 🔥 СОЗДАНИЕ УСЛОВИЙ АРЕНДЫ ИЗ ИНДИВИДУАЛЬНЫХ УСЛОВИЙ
     */
    private static function createRentalConditionFromIndividual(RentalRequestItem $requestItem, Company $company)
    {
        $individualConditions = $requestItem->individual_conditions;

        return RentalCondition::firstOrCreate([
            'company_id' => $company->id,
            'shift_hours' => $individualConditions['shift_hours'] ?? 8,
            'shifts_per_day' => $individualConditions['shifts_per_day'] ?? 1,
            'transportation' => $individualConditions['transportation'] ?? 'lessor',
            'fuel_responsibility' => $individualConditions['fuel_responsibility'] ?? 'lessor',
            'extension_policy' => $individualConditions['extension_policy'] ?? 'allowed',
            'payment_type' => $individualConditions['payment_type'] ?? 'hourly',
            'delivery_location_id' => $requestItem->rentalRequest->location_id,
            'is_default' => false,
        ]);
    }

    /**
     * 🔥 СОЗДАНИЕ УСЛОВИЙ АРЕНДЫ ИЗ ОБЩИХ УСЛОВИЙ ЗАЯВКИ
     */
    private static function createRentalConditionFromRequest(RentalRequest $rentalRequest)
    {
        $rentalConditions = $rentalRequest->rental_conditions ?? [];

        return RentalCondition::firstOrCreate([
            'company_id' => $rentalRequest->user->company_id,
            'shift_hours' => $rentalConditions['hours_per_shift'] ?? 8,
            'shifts_per_day' => $rentalConditions['shifts_per_day'] ?? 1,
            'transportation' => $rentalConditions['transportation_organized_by'] ?? 'lessor',
            'fuel_responsibility' => $rentalConditions['fuel_responsibility'] ?? 'lessor',
            'extension_policy' => $rentalConditions['extension_possibility'] ? 'allowed' : 'not_allowed',
            'payment_type' => $rentalConditions['payment_type'] ?? 'hourly',
            'delivery_location_id' => $rentalRequest->location_id,
            'is_default' => false,
        ]);
    }

    public function getTotalAttribute(): float
    {
        return ($this->base_price + $this->platform_fee) * $this->period_count;
    }
}
