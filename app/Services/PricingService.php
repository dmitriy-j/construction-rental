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
    public function calculatePrice(
        EquipmentRentalTerm $term,
        Company $lesseeCompany,
        int $workingHours,
        RentalCondition $condition
    ): array {
        // Базовая стоимость (цена арендодателя)
        $baseCost = $term->price_per_hour * $workingHours;

        // Наценка платформы
        $markup = $this->getPlatformMarkup($term->equipment, $lesseeCompany, $workingHours);
        $platformFee = $this->applyMarkup($baseCost, $markup);

        // Итоговая стоимость (базовая + наценка)
        $totalPrice = $baseCost + $platformFee;

        // Скидка арендатора
        $discount = $lesseeCompany->is_lessee
            ? $this->getDiscount($lesseeCompany, $totalPrice)
            : 0;

        // Цена за час с учетом наценки
        $pricePerHour = $totalPrice / max(1, $workingHours);

        return [
            'base_price' => $totalPrice,
            'platform_fee' => $platformFee,
            'discount_amount' => $discount,
            'final_price' => $totalPrice - $discount,
            'base_price_per_unit' => $pricePerHour,
            'working_hours' => $workingHours,
            'markup_type' => $markup['type'],
            'markup_value' => $markup['value'],
        ];
    }

    /**
     * РАСЧЕТ ЦЕНЫ ДЛЯ ПРЕДЛОЖЕНИЯ ПО ЗАЯВКЕ
     */
    public function calculateProposalPrice(Equipment $equipment, RentalRequest $request): array
    {
        $rentalTerm = $equipment->rentalTerms->first();
        if (!$rentalTerm) {
            throw new \Exception('Для оборудования не установлены условия аренды');
        }

        // Расчет рабочих часов
        $workingHours = $this->calculateWorkingHoursForRequest($request);

        // Базовая стоимость
        $baseCost = $rentalTerm->price_per_hour * $workingHours;

        // Наценка платформы
        $markup = $this->getPlatformMarkup($equipment, $request->user->company, $workingHours);
        $platformFee = $this->applyMarkup($baseCost, $markup);

        $totalPrice = $baseCost + $platformFee;

        // Скидка арендатора
        $discount = $request->user->company->is_lessee
            ? $this->getDiscount($request->user->company, $totalPrice)
            : 0;

        $finalPrice = $totalPrice - $discount;

        // Анализ соответствия бюджету заявки
        $budgetAnalysis = $this->analyzeBudgetCompatibility($finalPrice, $request);

        return [
            'base_cost' => $baseCost,
            'platform_fee' => $platformFee,
            'discount_amount' => $discount,
            'calculated_price' => $finalPrice,
            'working_hours' => $workingHours,
            'price_per_hour' => $finalPrice / max(1, $workingHours),

            // Анализ бюджета
            'budget_analysis' => $budgetAnalysis,
            'recommended_price' => $budgetAnalysis['recommended_price'],
            'is_within_budget' => $budgetAnalysis['is_within_budget'],

            // Маржа для арендодателя
            'lessor_margin' => $this->calculateLessorMargin($baseCost, $rentalTerm),
        ];
    }

    /**
     * АНАЛИЗ СООТВЕТСТВИЯ БЮДЖЕТУ ЗАЯВКИ
     */
    private function analyzeBudgetCompatibility(float $calculatedPrice, RentalRequest $request): array
    {
        $isWithinBudget = $calculatedPrice >= $request->budget_from &&
                         $calculatedPrice <= $request->budget_to;

        // Рекомендуемая цена (в рамках бюджета заявки)
        if ($calculatedPrice < $request->budget_from) {
            // Можно предложить цену повыше, но в рамках бюджета
            $recommendedPrice = min($calculatedPrice * 1.1, $request->budget_to); // +10%, но не выше макс. бюджета
        } elseif ($calculatedPrice > $request->budget_to) {
            // Нужно предложить цену пониже, но не ниже минимальной рентабельности
            $minProfitablePrice = $calculatedPrice * 0.8; // -20% как минимальная граница
            $recommendedPrice = max($minProfitablePrice, $request->budget_from);
        } else {
            // Цена в рамках бюджета - оставляем как есть
            $recommendedPrice = $calculatedPrice;
        }

        return [
            'is_within_budget' => $isWithinBudget,
            'recommended_price' => round($recommendedPrice, 2),
            'budget_gap' => $isWithinBudget ? 0 : round(abs($calculatedPrice - $request->budget_to), 2),
            'budget_percentage' => $calculatedPrice / $request->budget_to * 100,
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
        $markup = $this->findMarkup(Equipment::class, $equipment->id);
        $source = 'equipment';

        if (! $markup) {
            $markup = $this->findMarkup(EquipmentCategory::class, $equipment->category_id);
            $source = 'category';
        }

        if (! $markup && $lesseeCompany && $lesseeCompany->is_lessee) {
            $markup = $this->findMarkup(Company::class, $lesseeCompany->id);
            $source = 'company';
        }

        if (! $markup) {
            $markup = $this->getDefaultMarkup();
            $source = 'default';
        }

        $originalValue = $markup['value'];

        if ($markup['type'] === 'fixed') {
            $markup['value'] *= $workingHours;
        }

        Log::debug('Applied platform markup', [
            'equipment_id' => $equipment->id,
            'company_id' => $lesseeCompany?->id,
            'source' => $source,
            'type' => $markup['type'],
            'original_value' => $originalValue,
            'final_value' => $markup['value'],
            'working_hours' => $workingHours,
        ]);

        return $markup;
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
        return $markup['type'] === 'percent'
            ? $price * ($markup['value'] / 100)
            : $markup['value'];
    }
}
