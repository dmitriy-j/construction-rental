<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\DiscountTier;
use App\Models\PlatformMarkup;
use App\Models\EquipmentCategory;
use App\Models\RentalCondition; // Исправленный импорт
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PricingService
{
    public function calculatePrice(
        EEquipmentRentalTerm $term,
        Company $lesseeCompany,
        int $periodCount,
        RentalCondition $condition
    ): array {
        // Базовая стоимость
        $basePrice = $term->price_per_hour * $periodCount;

        // Добавляем дополнительные расходы
        $additionalCosts = 0;

        // Транспортировка
        if ($condition->transportation === 'lessee') {
            $additionalCosts += $term->delivery_price;
        } elseif ($condition->transportation === 'shared') {
            $additionalCosts += $term->delivery_price / 2;
        }

        // ГСМ
        if ($condition->fuel_responsibility === 'lessee') {
            $fuelCost = $this->calculateFuelCost($term, $periodCount, $condition);
            $additionalCosts += $fuelCost;
        }

        $basePrice += $additionalCosts;

        // Наценка платформы
        $markup = $this->getPlatformMarkup($term->equipment, $lesseeCompany, $periodCount);
        $platformFee = $this->applyMarkup($basePrice, $markup);

        // Скидка арендатора
        $discount = $lesseeCompany->is_lessee
            ? $this->getDiscount($lesseeCompany, $basePrice + $platformFee)
            : 0;

        return [
            'base_price' => $basePrice,
            'platform_fee' => $platformFee,
            'discount_amount' => $discount,
            'final_price' => $basePrice + $platformFee - $discount,
            'base_price_per_unit' => $term->price_per_hour, // Было: $term->price
            'platform_fee_per_unit' => $platformFee / max(1, $periodCount),
            'markup_type' => $markup['type'],
            'markup_value' => $markup['value']
        ];
    }

     private function calculateFuelCost(
        EquipmentRentalTerm $term,
        int $periodCount,
        RentalCondition $condition
    ): float {
        // Простая реализация расчета стоимости топлива
        // Используем данные из оборудования и условий аренды

        // Если в оборудовании указан расход топлива
        $fuelConsumption = $term->equipment->fuel_consumption ?? 0;

        // Если в условиях аренды указана средняя цена топлива
        $fuelPrice = $condition->average_fuel_price ?? 0;

        // Расчет: расход * цена * количество периодов
        return $fuelConsumption * $fuelPrice * $periodCount;
    }

    public function getDiscount(Company $company, float $amount): float
    {
        $tier = DiscountTier::where('company_id', $company->id)
            ->where('min_turnover', '<=', $amount)
            ->orderBy('min_turnover', 'desc')
            ->first();

        return $tier ? $amount * ($tier->discount_percent / 100) : 0;
    }

    private function getPlatformMarkup(Equipment $equipment, ?Company $lesseeCompany, int $periodCount): array
    {
        // Приоритет наценок:
        // 1. Конкретное оборудование
        // 2. Категория оборудования
        // 3. Компания-арендатор (если это арендатор)
        // 4. Базовая наценка платформы

        $markup = $this->findMarkup(Equipment::class, $equipment->id);
        $source = 'equipment';

        if (!$markup) {
            $markup = $this->findMarkup(EquipmentCategory::class, $equipment->category_id);
            $source = 'category';
        }

        // Проверяем, является ли компания арендатором
        if (!$markup && $lesseeCompany && $lesseeCompany->is_lessee) {
            $markup = $this->findMarkup(Company::class, $lesseeCompany->id);
            $source = 'company';
        }

        if (!$markup) {
            $markup = $this->getDefaultMarkup();
            $source = 'default';
        }

        $originalValue = $markup['value'];

        // Для фиксированных наценок применяем множитель периода
        if ($markup['type'] === 'fixed') {
            $markup['value'] *= $periodCount;
        }

        // Логирование
        Log::debug('Applied markup', [
            'equipment_id' => $equipment->id,
            'company_id' => $lesseeCompany?->id,
            'source' => $source,
            'type' => $markup['type'],
            'original_value' => $originalValue,
            'final_value' => $markup['value'],
            'period_count' => $periodCount
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

    private function applyMarkup(float $price, array $markup): float
    {
        return $markup['type'] === 'percent'
            ? $price * ($markup['value'] / 100)
            : $markup['value'];
    }
};
