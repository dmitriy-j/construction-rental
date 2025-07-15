<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\DiscountTier;
use App\Models\PlatformMarkup;
use App\Models\EquipmentCategory;
use App\Models\RentalCondition;
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
            'base_price' => $totalPrice, // Общая стоимость с наценкой
            'platform_fee' => $platformFee,
            'discount_amount' => $discount,
            'final_price' => $totalPrice - $discount,
            'base_price_per_unit' => $pricePerHour,
            'working_hours' => $workingHours,
            'markup_type' => $markup['type'],
            'markup_value' => $markup['value']
        ];
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
        // Приоритет наценок: оборудование > категория > компания > базовая
        $markup = $this->findMarkup(Equipment::class, $equipment->id);
        $source = 'equipment';

        if (!$markup) {
            $markup = $this->findMarkup(EquipmentCategory::class, $equipment->category_id);
            $source = 'category';
        }

        if (!$markup && $lesseeCompany && $lesseeCompany->is_lessee) {
            $markup = $this->findMarkup(Company::class, $lesseeCompany->id);
            $source = 'company';
        }

        if (!$markup) {
            $markup = $this->getDefaultMarkup();
            $source = 'default';
        }

        $originalValue = $markup['value'];

        // Для фиксированных наценок применяем множитель часов
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
            'working_hours' => $workingHours
        ]);

        return $markup;
    }

    private function findMarkup(string $markupableType, ?int $markupableId): ?array
    {
        if (!$markupableId) return null;

        return Cache::remember("markup_{$markupableType}_{$markupableId}", 3600, function() use ($markupableType, $markupableId) {
            $markup = PlatformMarkup::where('platform_id', 1)
                ->where('markupable_type', $markupableType)
                ->where('markupable_id', $markupableId)
                ->first();

            return $markup ? [
                'type' => $markup->type,
                'value' => $markup->value
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
            'value' => $markup->value
        ] : ['type' => 'fixed', 'value' => 0];
    }

    public function applyMarkup(float $price, array $markup): float
    {
        return $markup['type'] === 'percent'
            ? $price * ($markup['value'] / 100)
            : $markup['value'];
    }
}
