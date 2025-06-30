<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\DiscountTier;
use App\Models\PlatformMarkup;
use App\Models\EquipmentCategory;
use Illuminate\Support\Facades\Cache; // Добавляем этот импорт


class PricingService
{
    public function calculatePrice(
        EquipmentRentalTerm $term,
        ?Company $lesseeCompany,
        int $periodCount
    ): array {
        // Базовая стоимость без наценки
        $basePrice = $term->price * $periodCount;

        // Наценка платформы - используем новую систему
        $markup = $this->getPlatformMarkup($term->equipment, $lesseeCompany, $periodCount);
        $platformFee = $this->applyMarkup($basePrice, $markup);

        // Скидка арендатора (если компания указана)
        $discount = $lesseeCompany
            ? $this->getDiscount($lesseeCompany, $basePrice + $platformFee)
            : 0;

        return [
            'base_price' => $basePrice,
            'platform_fee' => $platformFee,
            'discount_amount' => $discount,
            'final_price' => $basePrice + $platformFee - $discount,
            'base_price_per_unit' => (float) $term->price,
            'platform_fee_per_unit' => $platformFee / max(1, $periodCount),
            'markup_type' => $markup['type'],
            'markup_value' => $markup['value']
        ];
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
        // 3. Компания-арендатор
        // 4. Базовая наценка платформы

        $markup = $this->findMarkup(Equipment::class, $equipment->id);
        $source = 'equipment'; // Фиксируем источник

        if (!$markup) {
            $markup = $this->findMarkup(EquipmentCategory::class, $equipment->category_id);
            $source = 'category';
        }

        if (!$markup && $lesseeCompany) {
            $markup = $this->findMarkup(Company::class, $lesseeCompany->id);
            $source = 'company';
        }

        if (!$markup) {
            $markup = $this->getDefaultMarkup();
            $source = 'default';
        }

        $originalValue = $markup['value']; // Сохраняем оригинальное значение

        // Для фиксированных наценок применяем множитель периода
        if ($markup['type'] === 'fixed') {
            $markup['value'] *= $periodCount;
        }

        // Логирование с безопасным доступом к данным
        \Log::info('Applied markup', [
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
}
