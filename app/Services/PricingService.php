<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\DiscountTier;

class PricingService
{
    public function calculatePrice(
            EquipmentRentalTerm $term,
            ?Company $lesseeCompany,
            int $periodCount
        ): array {
            // Базовая стоимость без наценки
            $basePrice = $term->price * $periodCount;

            // Наценка платформы
            $markup = $this->getMarkup($term->equipment, $periodCount);
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

        private function getMarkup(Equipment $equipment, int $periodCount): array
        {
            // Проверяем наличие категории
            if (!$equipment->relationLoaded('category')) {
                $equipment->load('category');
            }

            $categoryName = $equipment->category->name ?? null;

            // Наценки в зависимости от категории
            $markupValue = match ($categoryName) {
                'Тяжелая техника' => 300 * $periodCount,
                'Премиум техника' => 500 * $periodCount,
                default => 200 * $periodCount,
            };

            return [
                'type' => 'fixed',
                'value' => $markupValue
            ];
        }

        private function applyMarkup(float $price, array $markup): float
            {
                return $markup['type'] === 'percent'
                    ? $price * ($markup['value'] / 100)
                    : $markup['value'];
            }
}
