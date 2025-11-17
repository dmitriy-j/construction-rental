<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DiscountTier;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentRentalTerm;
use App\Models\PlatformMarkup;
use App\Models\RentalCondition;
use App\Models\RentalRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PricingService
{

    protected $markupCalculationService;

    public function __construct(MarkupCalculationService $markupCalculationService)
    {
        $this->markupCalculationService = $markupCalculationService;
    }

    public function calculatePrice(
        EquipmentRentalTerm $term,
        Company $lesseeCompany,
        int $workingHours,
        RentalCondition $condition
    ): array {
        // Базовая стоимость (цена арендодателя)
        $baseCost = $term->price_per_hour * $workingHours;

        // НОВЫЙ РАСЧЕТ: Используем унифицированный сервис наценок
        $markupResult = $this->markupCalculationService->calculateMarkup(
            $term->price_per_hour, // цена за час
            'order', // контекст - заказ
            $workingHours,
            $term->equipment_id,
            $term->equipment->category_id,
            null, // компания арендодателя не учитывается в наценках
            $lesseeCompany->id // компания арендатора
        );

        // Пересчитываем platform_fee на основе общего количества часов
        $platformFee = $markupResult['markup_amount'];

        // Итоговая стоимость (базовая + наценка)
        $totalPrice = $baseCost + $platformFee;

        // Скидка арендатора (оставляем существующую логику)
        $discount = $lesseeCompany->is_lessee
            ? $this->getDiscount($lesseeCompany, $totalPrice)
            : 0;

        // Цена за час с учетом наценки
        $pricePerHour = $totalPrice / max(1, $workingHours);

        Log::debug('Pricing calculation with new markup system', [
            'equipment_id' => $term->equipment_id,
            'base_price_per_hour' => $term->price_per_hour,
            'working_hours' => $workingHours,
            'base_cost' => $baseCost,
            'platform_fee' => $platformFee,
            'total_price' => $totalPrice,
            'markup_details' => $markupResult
        ]);

        return [
            'base_price' => $totalPrice,
            'platform_fee' => $platformFee,
            'discount_amount' => $discount,
            'final_price' => $totalPrice - $discount,
            'base_price_per_unit' => $pricePerHour,
            'working_hours' => $workingHours,
            'markup_type' => $markupResult['markup_type'],
            'markup_value' => $markupResult['markup_value'],
            'markup_details' => $markupResult,
        ];
    }

    /**
     * РАСЧЕТ ЦЕНЫ ДЛЯ ПРЕДЛОЖЕНИЯ ПО ЗАЯВКЕ
     */
    public function calculateProposalPrice(
        Equipment $equipment,
        RentalRequest $request,
        float $proposedPrice, // Цена арендодателя
        int $workingHours
    ): array {
        // НОВЫЙ РАСЧЕТ: Используем унифицированный сервис наценок для контекста rental_request
        $markupResult = $this->markupCalculationService->calculateMarkup(
            $proposedPrice, // цена арендодателя за час
            'rental_request', // контекст - заявка
            $workingHours,
            $equipment->id,
            $equipment->category_id,
            null, // компания арендодателя
            $request->user->company_id // компания арендатора
        );

        // Базовая стоимость (цена арендодателя)
        $baseCost = $proposedPrice * $workingHours;

        // Наценка платформы из нового сервиса
        $platformFee = $markupResult['markup_amount'];

        // Итоговая стоимость (базовая + наценка)
        $totalPrice = $baseCost + $platformFee;

        // Цена за час с учетом наценки
        $pricePerHour = $totalPrice / max(1, $workingHours);

        Log::debug('Proposal pricing calculation with new markup system', [
            'equipment_id' => $equipment->id,
            'proposed_price' => $proposedPrice,
            'working_hours' => $workingHours,
            'platform_fee' => $platformFee,
            'total_price' => $totalPrice,
            'markup_details' => $markupResult
        ]);

        return [
            'calculated_price' => $totalPrice,
            'price_per_hour' => $pricePerHour,
            'platform_fee' => $platformFee,
            'working_hours' => $workingHours,
            'markup_details' => $markupResult,
        ];
    }

    /**
     * АНАЛИЗ СООТВЕТСТВИЯ БЮДЖЕТУ ЗАЯВКИ
     */
     private function analyzeBudgetCompatibility(float $calculatedPrice, RentalRequest $request): array
    {
        \Log::info("Analyzing budget compatibility:", [
            'calculated_price' => $calculatedPrice,
            'budget_from' => $request->budget_from,
            'budget_to' => $request->budget_to,
            'types' => [
                'calculated_type' => gettype($calculatedPrice),
                'budget_from_type' => gettype($request->budget_from),
                'budget_to_type' => gettype($request->budget_to)
            ]
        ]);

        // ЯВНОЕ ПРЕОБРАЗОВАНИЕ В ЧИСЛА ПЕРЕД ОПЕРАЦИЯМИ
        $budgetFrom = (float) $request->budget_from;
        $budgetTo = (float) $request->budget_to;

        $isWithinBudget = $calculatedPrice >= $budgetFrom &&
                         $calculatedPrice <= $budgetTo;

        // Рекомендуемая цена (в рамках бюджета заявки)
        if ($calculatedPrice < $budgetFrom) {
            // Можно предложить цену повыше, но в рамках бюджета
            $recommendedPrice = min($calculatedPrice * 1.1, $budgetTo);
        } elseif ($calculatedPrice > $budgetTo) {
            // Нужно предложить цену пониже, но не ниже минимальной рентабельности
            $minProfitablePrice = $calculatedPrice * 0.8;
            $recommendedPrice = max($minProfitablePrice, $budgetFrom);
        } else {
            // Цена в рамках бюджета - оставляем как есть
            $recommendedPrice = $calculatedPrice;
        }

        // ИСПРАВЛЕННЫЙ РАСЧЕТ ПРОЦЕНТОВ - ИЗБЕГАЕМ ДЕЛЕНИЯ НА НОЛЬ
        $budgetPercentage = $budgetTo > 0 ? ($calculatedPrice / $budgetTo * 100) : 0;

        return [
            'is_within_budget' => $isWithinBudget,
            'recommended_price' => round($recommendedPrice, 2),
            'budget_gap' => $isWithinBudget ? 0 : round(abs($calculatedPrice - $budgetTo), 2),
            'budget_percentage' => $budgetPercentage,
        ];
    }

    /**
     * РАСЧЕТ МАРЖИ АРЕНДОДАТЕЛЯ
     */
    private function calculateLessorMargin(float $baseCost, EquipmentRentalTerm $term): array
    {
        // Примерная себестоимость (можно добавить поле в модель Equipment)
        $costPrice = $baseCost * 0.6; // Предполагаем 40% маржу

        $margin = $baseCost - $costPrice;
        $marginPercentage = ($margin / $baseCost) * 100;

        return [
            'margin_amount' => $margin,
            'margin_percentage' => $marginPercentage,
            'cost_price' => $costPrice,
            'is_profitable' => $marginPercentage > 20, // Минимальная рентабельность 20%
        ];
    }

    /**
     * РАСЧЕТ РАБОЧИХ ЧАСОВ ДЛЯ ЗАЯВКИ
     */
    private function calculateWorkingHoursForRequest(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        $days = $start->diffInDays($end) + 1;

        // Учитываем желаемые характеристики из заявки
        $desiredSpecs = $request->desired_specifications ? json_decode($request->desired_specifications, true) : [];

        $shiftHours = $desiredSpecs['shift_hours'] ?? 8;
        $shiftsPerDay = $desiredSpecs['shifts_per_day'] ?? 1;

        return $days * $shiftHours * $shiftsPerDay;
    }

    private function calculateFuelCost(
        EquipmentRentalTerm $term,
        int $workingHours,
        RentalCondition $condition
    ): float {
        $fuelConsumption = $term->equipment->fuel_consumption ?? 0;
        $fuelPrice = $condition->average_fuel_price ?? 0;

        return $fuelConsumption * $fuelPrice * $workingHours;
    }

    public function getDiscount(Company $company, float $amount): float
    {
        $tier = DiscountTier::where('company_id', $company->id)
            ->where('min_turnover', '<=', $amount)
            ->orderBy('min_turnover', 'desc')
            ->first();

        return $tier ? $amount * ($tier->discount_percent / 100) : 0;
    }

    public function getPlatformMarkup(Equipment $equipment, ?Company $lesseeCompany, int $workingHours): array
    {
        $markupResult = $this->markupCalculationService->calculateMarkup(
            $equipment->rentalTerms->first()?->price_per_hour ?? 0,
            'order',
            $workingHours,
            $equipment->id,
            $equipment->category_id,
            null,
            $lesseeCompany?->id
        );

        return [
            'type' => $markupResult['markup_type'],
            'value' => $markupResult['markup_value'],
            'amount' => $markupResult['markup_amount'],
            'details' => $markupResult
        ];
    }

    private function findMarkup(string $markupableType, ?int $markupableId): ?array
    {
        if (! $markupableId) {
            return null;
        }

        return Cache::remember("markup_{$markupableType}_{$markupableId}", 3600, function () use ($markupableType, $markupableId) {
            $markup = PlatformMarkup::where('platform_id', 1)
                ->where('markupable_type', $markupableType)
                ->where('markupable_id', $markupableId)
                ->first();

            return $markup ? [
                'type' => $markup->type,
                'value' => $markup->value,
            ] : null;
        });
    }

    private function getDefaultMarkup(): array
    {
        $markup = PlatformMarkup::where('platform_id', 1)
            ->whereNull('markupable_type')
            ->whereNull('markupable_id')
            ->first();

        return $markup ? [
            'type' => $markup->type,
            'value' => $markup->value,
        ] : ['type' => 'fixed', 'value' => 0];
    }

    public function applyMarkup(float $price, array $markup): float
    {
        // Для обратной совместимости оставляем старый метод,
        // но теперь он использует данные из нового сервиса
        if (isset($markup['amount'])) {
            return $markup['amount'];
        }

        return $markup['type'] === 'percent'
            ? $price * ($markup['value'] / 100)
            : $markup['value'];
    }
}
