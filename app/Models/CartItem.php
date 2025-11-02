<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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

    /**
     * Создание элемента корзины из предложения
     */
    public static function createFromProposal(RentalRequestResponse $proposal, Cart $cart): self
    {
        $equipment = $proposal->equipment;
        $rentalRequest = $proposal->rentalRequest;

        // 🔥 ПОЛУЧАЕМ УСЛОВИЯ АРЕНДЫ ИЗ ЗАЯВКИ
        $rentalCondition = self::getRentalConditionForProposal($proposal, $rentalRequest);

        // 🔥 РАСЧЕТ РАБОЧИХ ЧАСОВ С УЧЕТОМ РЕАЛЬНЫХ УСЛОВИЙ
        $workingHours = self::calculateActualWorkingHours($rentalRequest, $rentalCondition);

        // 🔥 ПРИОРИТЕТ 1: Используем price_breakdown если он есть и корректен
        $priceBreakdown = $proposal->price_breakdown;
        if (is_string($priceBreakdown)) {
            $priceBreakdown = json_decode($priceBreakdown, true);
        }

        $customerPricePerHour = null;
        $lessorPricePerHour = null;

        // 🔥 ИЗВЛЕКАЕМ ДАННЫЕ О ДОСТАВКЕ ИЗ PRICE_BREAKDOWN
        $deliveryBreakdown = $priceBreakdown['delivery_breakdown'] ?? [];
        $hasDelivery = $deliveryBreakdown['delivery_required'] ?? false;
        $deliveryCost = $deliveryBreakdown['delivery_cost'] ?? 0;

        if (!empty($priceBreakdown) && is_array($priceBreakdown)) {
            // Для обычных предложений
            if (isset($priceBreakdown['customer_price_per_unit'])) {
                $customerPricePerHour = $priceBreakdown['customer_price_per_unit'];
                $lessorPricePerHour = $priceBreakdown['lessor_price_per_unit'] ?? $customerPricePerHour - 100;
            }
            // Для bulk-предложений
            elseif (isset($priceBreakdown['items']) && is_array($priceBreakdown['items'])) {
                foreach ($priceBreakdown['items'] as $item) {
                    if ($item['equipment_id'] == $proposal->equipment_id) {
                        $customerPricePerHour = $item['customer_price_per_unit'] ?? null;
                        $lessorPricePerHour = $item['lessor_price_per_unit'] ?? null;

                        // 🔥 ИЗВЛЕКАЕМ ДОСТАВКУ ДЛЯ BULK-ПРЕДЛОЖЕНИЙ
                        $deliveryBreakdown = $item['delivery_breakdown'] ?? [];
                        $hasDelivery = $deliveryBreakdown['delivery_required'] ?? false;
                        $deliveryCost = $deliveryBreakdown['delivery_cost'] ?? 0;
                        break;
                    }
                }
            }
        }

        // 🔥 ПРИОРИТЕТ 2: Если price_breakdown отсутствует, используем расчет на основе данных заявки
        if (!$customerPricePerHour || !$lessorPricePerHour) {
            \Log::warning('Price breakdown missing or incomplete for proposal', [
                'proposal_id' => $proposal->id,
                'price_breakdown' => $priceBreakdown
            ]);

            // Находим соответствующую позицию в заявке для получения hourly_rate
            $requestItem = $rentalRequest->items->first(function ($item) use ($equipment) {
                return $item->category_id == $equipment->category_id;
            });

            if ($requestItem && $requestItem->hourly_rate > 0) {
                // Используем hourly_rate из заявки как базовую цену
                $customerPricePerHour = $requestItem->hourly_rate;
                $lessorPricePerHour = $customerPricePerHour - 100; // Базовая наценка 100 ₽
            } else {
                // 🔥 РЕЗЕРВНЫЙ РАСЧЕТ: Делим общую сумму на реальное количество часов
                if ($proposal->proposed_price > 0 && $workingHours > 0 && $proposal->proposed_quantity > 0) {
                    $customerPricePerHour = $proposal->proposed_price / ($workingHours * $proposal->proposed_quantity);
                    $lessorPricePerHour = $customerPricePerHour - 100;
                } else {
                    // Последний fallback
                    $customerPricePerHour = $rentalRequest->hourly_rate ?? 1000;
                    $lessorPricePerHour = $customerPricePerHour - 100;
                    \Log::warning('Using fallback price calculation', ['price' => $customerPricePerHour]);
                }
            }
        }

        // 🔥 ВАЛИДАЦИЯ: Проверяем корректность цен
        if ($customerPricePerHour <= 0 || $lessorPricePerHour <= 0) {
            throw new \Exception('Некорректные цены в предложении: цена за час должна быть положительной');
        }

        // Рассчитываем platform_fee (доход платформы)
        $platformFeePerHour = $customerPricePerHour - $lessorPricePerHour;
        $totalPlatformFee = $platformFeePerHour * $workingHours * ($proposal->proposed_quantity ?? 1);

        return self::create([
            'cart_id' => $cart->id,
            'rental_term_id' => $equipment->rentalTerms->first()->id,
            'proposal_id' => $proposal->id,
            'rental_request_item_id' => $proposal->rental_request_item_id,
            'rental_condition_id' => $rentalCondition->id, // 🔥 ТЕПЕРЬ СОХРАНЯЕМ УСЛОВИЯ
            'is_proposal_item' => true,
            'period_count' => $workingHours,
            'base_price' => $customerPricePerHour,
            'fixed_customer_price' => $customerPricePerHour,
            'fixed_lessor_price' => $lessorPricePerHour,
            'platform_fee' => $platformFeePerHour,
            'start_date' => $rentalRequest->rental_period_start,
            'end_date' => $rentalRequest->rental_period_end,
            'delivery_cost' => $deliveryCost, // 🔥 СОХРАНЯЕМ СТОИМОСТЬ ДОСТАВКИ
            'proposal_data' => [
                'original_proposal_price' => $proposal->proposed_price,
                'lessor_company_id' => $proposal->lessor->company_id,
                'equipment_title' => $equipment->title,
                'proposal_message' => $proposal->message,
                'total_working_hours' => $workingHours,
                'customer_price_per_hour' => $customerPricePerHour,
                'lessor_price_per_hour' => $lessorPricePerHour,
                'platform_fee_per_hour' => $platformFeePerHour,
                'total_platform_fee' => $totalPlatformFee,
                'price_breakdown_source' => empty($priceBreakdown) ? 'calculated' : 'from_proposal',
                'calculation_notes' => empty($priceBreakdown) ? 'Цены рассчитаны на основе hourly_rate из заявки' : 'Цены взяты из breakdown',
                // 🔥 ДОБАВЛЯЕМ ДАННЫЕ О ДОСТАВКЕ
                'delivery_breakdown' => $deliveryBreakdown,
                'has_delivery' => $hasDelivery,
                'delivery_cost' => $deliveryCost
            ],
        ]);
    }

    /**
     * 🔥 РАСЧЕТ РАБОЧИХ ЧАСОВ С УЧЕТОМ УСЛОВИЙ АРЕНДЫ
     */
    private static function calculateActualWorkingHours($rentalRequest, $rentalCondition): int
    {
        if (!$rentalRequest->rental_period_start || !$rentalRequest->rental_period_end) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($rentalRequest->rental_period_start);
        $end = \Carbon\Carbon::parse($rentalRequest->rental_period_end);

        // Если есть условия аренды, используем их для расчета
        if ($rentalCondition) {
            return self::calculateWorkingHoursWithConditions($start, $end, $rentalCondition);
        }

        // Стандартный расчет: исключаем выходные
        return self::calculateStandardWorkingHours($start, $end);
    }

    /**
     * 🔥 РАСЧЕТ ЧАСОВ С УЧЕТОМ УСЛОВИЙ АРЕНДЫ
     */
    private static function calculateWorkingHoursWithConditions($start, $end, $rentalCondition): int
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
    private static function calculateStandardWorkingHours($start, $end): int
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
     * 🔥 ПОЛУЧЕНИЕ УСЛОВИЙ АРЕНДЫ ДЛЯ ПРЕДЛОЖЕНИЯ
     */
    private static function getRentalConditionForProposal(RentalRequestResponse $proposal, RentalRequest $rentalRequest)
    {
        // Если есть индивидуальные условия для позиции заявки
        if ($proposal->rental_request_item_id) {
            $requestItem = RentalRequestItem::find($proposal->rental_request_item_id);
            if ($requestItem && $requestItem->use_individual_conditions && !empty($requestItem->individual_conditions)) {
                return self::createRentalConditionFromIndividual($requestItem, $rentalRequest->user->company);
            }
        }

        // Используем общие условия заявки
        return self::createRentalConditionFromRequest($rentalRequest);
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

    public function rentalCondition()
    {
        return $this->belongsTo(RentalCondition::class);
    }

    public function deliveryFrom(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_from_id');
    }

    public function deliveryTo(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_to_id');
    }
}
