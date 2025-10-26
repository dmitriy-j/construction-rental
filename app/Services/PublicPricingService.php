<?php
// app/Services/PublicPricingService.php

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\Equipment;
use App\Models\RentalRequestResponse;
use App\Models\User;
use App\Models\Company;
use App\Models\PlatformMarkup;
use App\Models\EquipmentCategory;

class PublicPricingService
{
    public function calculatePlatformMarkup(RentalRequest $request, Equipment $equipment, float $proposedPrice): array
    {
        // Базовая цена арендодателя
        $lessorPrice = $proposedPrice;

        // Расчет экономии клиента
        $clientSaving = $request->calculateClientSaving($proposedPrice);

        // Комбинированная наценка: 100₽ + 30% от экономии
        $fixedMarkup = 100;
        $percentageMarkup = $clientSaving * 0.3;
        $totalMarkup = $fixedMarkup + $percentageMarkup;

        // Финальная цена для арендатора
        $finalPrice = $lessorPrice + $totalMarkup;

        return [
            'lessor_price' => $lessorPrice,
            'client_saving' => $clientSaving,
            'platform_markup' => [
                'fixed' => $fixedMarkup,
                'percentage' => $percentageMarkup,
                'total' => $totalMarkup,
            ],
            'final_price' => $finalPrice,
            'calculation_formula' => 'lessor_price + 100 + (client_saving × 0.3)'
        ];
    }

    public function createProposalWithMarkup(RentalRequest $request, array $data): RentalRequestResponse
    {
        $equipment = Equipment::findOrFail($data['equipment_id']);

        // Расчет наценки
        $markupDetails = $this->calculatePlatformMarkup(
            $request,
            $equipment,
            $data['proposed_price']
        );

        // Создание предложения
        $proposal = RentalRequestResponse::create([
            'rental_request_id' => $request->id,
            'lessor_id' => auth()->id(),
            'equipment_id' => $data['equipment_id'],
            'proposed_price' => $data['proposed_price'],
            'proposed_quantity' => $data['proposed_quantity'] ?? 1,
            'message' => $data['message'],
            'price_breakdown' => $markupDetails,
            'platform_markup_details' => $markupDetails['platform_markup'],
            'status' => 'pending',
            'reservation_status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Резервирование оборудования на 24 часа при подтверждении
        $this->reserveEquipmentIfAccepted($proposal);

        return $proposal;
    }

    private function reserveEquipmentIfAccepted(RentalRequestResponse $proposal): void
    {
        // Эта функция будет вызвана при принятии предложения
        // Пока просто устанавливаем флаг резервирования
        $proposal->update([
            'reservation_status' => 'pending',
            'reserved_until' => now()->addDay()
        ]);
    }

    public function calculateBulkPricing(array $equipmentProposals, RentalRequest $request): array
    {
        $totalLessorPrice = 0;
        $totalFinalPrice = 0;
        $proposalDetails = [];

        foreach ($equipmentProposals as $proposal) {
            $pricing = $this->calculatePlatformMarkup(
                $request,
                Equipment::find($proposal['equipment_id']),
                $proposal['proposed_price']
            );

            // Расчет стоимости за период
            $periodCost = $this->calculatePeriodCost(
                $pricing['final_price'],
                $proposal['quantity'],
                $request
            );

            $proposalDetails[] = [
                'equipment_id' => $proposal['equipment_id'],
                'quantity' => $proposal['quantity'],
                'lessor_price_per_unit' => $proposal['proposed_price'],
                'pricing_details' => $pricing,
                'period_cost' => $periodCost
            ];

            $totalLessorPrice += $proposal['proposed_price'] * $proposal['quantity'];
            $totalFinalPrice += $periodCost['total_cost'];
        }

        return [
            'total_lessor_price' => $totalLessorPrice,
            'total_final_price' => $totalFinalPrice,
            'platform_markup_total' => $totalFinalPrice - $totalLessorPrice,
            'proposals' => $proposalDetails,
            'rental_days' => $this->calculateRentalDays($request)
        ];
    }

    private function calculatePeriodCost(float $pricePerUnit, int $quantity, RentalRequest $request): array
    {
        $rentalDays = $this->calculateRentalDays($request);
        $hoursPerDay = ($request->rental_conditions['hours_per_shift'] ?? 8) *
                      ($request->rental_conditions['shifts_per_day'] ?? 1);

        $costPerDay = $pricePerUnit * $hoursPerDay * $quantity;
        $totalCost = $costPerDay * $rentalDays;

        return [
            'price_per_unit' => $pricePerUnit,
            'quantity' => $quantity,
            'hours_per_day' => $hoursPerDay,
            'cost_per_day' => $costPerDay,
            'rental_days' => $rentalDays,
            'total_cost' => $totalCost
        ];
    }

    private function calculateRentalDays(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        return $start->diffInDays($end) + 1;
    }

    public function getAvailableEquipmentWithPricing(RentalRequest $request, User $lessor): array
    {
        \Log::info("🔍 Starting equipment search for request: " . $request->id);

        // Получаем категории из заявки
        $requestCategoryIds = $request->items->pluck('category_id')->toArray();

        \Log::info("📋 Request categories: " . json_encode($requestCategoryIds));

        // Используем существующие поля
        $companyEquipment = Equipment::where('company_id', $lessor->company_id)
            ->whereIn('category_id', $requestCategoryIds)
            ->where('is_approved', 1) // Только одобренное оборудование
            ->with('category', 'rentalTerms')
            ->get();

        \Log::info("🏗️ Found equipment: " . $companyEquipment->count() . " items");

        $equipmentWithPricing = [];

        foreach ($companyEquipment as $equipment) {
            \Log::info("💰 Processing equipment: " . $equipment->title);

            // Расчет рекомендуемой цены
            $recommendedPrice = $this->calculateRecommendedLessorPrice($equipment, $request);

            // Расчет финальной цены с наценкой
            $pricing = $this->calculatePlatformMarkup($request, $equipment, $recommendedPrice);

            $equipmentWithPricing[] = [
                'equipment' => $equipment,
                'recommended_lessor_price' => $recommendedPrice,
                'final_customer_price' => $pricing['final_price'],
                'platform_markup' => $pricing['platform_markup'],
                'max_available_quantity' => 1
            ];
        }

        return ['available_equipment' => $equipmentWithPricing];
    }

    private function calculateRecommendedLessorPrice(Equipment $equipment, RentalRequest $request): float
    {
        \Log::info("💰 Calculating recommended price for equipment: {$equipment->title}");

        // 1. Находим соответствующую позицию в заявке по категории
        $matchingItem = $request->items->first(function ($item) use ($equipment) {
            return $item->category_id === $equipment->category_id;
        });

        // 2. Получаем бюджет арендатора для этой категории
        $customerBudget = $matchingItem->hourly_rate ?? $request->hourly_rate ?? 0;

        \Log::info("📊 Customer budget for category: {$customerBudget}");

        if ($customerBudget <= 0) {
            \Log::warning("❌ No customer budget found, using equipment base price");
            return $equipment->rentalTerms->first()?->price_per_hour ?? 1000;
        }

        // 3. Получаем наценку платформы для этого оборудования
        $markup = $this->getPlatformMarkupForRentalRequest($equipment->category_id, $request->user->company);
        \Log::info("🏷️ Platform markup:", $markup);

        // 4. Рассчитываем рекомендуемую цену для арендодателя
        $recommendedPrice = $this->reverseApplyMarkup($customerBudget, $markup, 1); // 1 час для расчета

        \Log::info("🎯 Final recommended price: {$recommendedPrice} (from customer: {$customerBudget})");

        // 5. Обеспечиваем минимальную цену
        $minPrice = $equipment->rentalTerms->first()?->price_per_hour ?? 500;
        $finalPrice = max($recommendedPrice, $minPrice);

        \Log::info("✅ Final price after min check: {$finalPrice}");

        return $finalPrice;
    }

    public function getPlatformMarkupForRentalRequest(?int $categoryId, ?Company $lesseeCompany): array
    {
        // Сначала ищем наценку для категории оборудования
        if ($categoryId) {
            $markup = $this->findMarkupForRentalRequest(EquipmentCategory::class, $categoryId);
            if ($markup) {
                \Log::debug('Found markup for category', ['category_id' => $categoryId, 'markup' => $markup]);
                return $markup;
            }
        }

        // Затем для компании арендатора
        if ($lesseeCompany && $lesseeCompany->is_lessee) {
            $markup = $this->findMarkupForRentalRequest(Company::class, $lesseeCompany->id);
            if ($markup) {
                \Log::debug('Found markup for company', ['company_id' => $lesseeCompany->id, 'markup' => $markup]);
                return $markup;
            }
        }

        // Ищем общую наценку для заявок
        $markup = PlatformMarkup::where('entity_type', 'rental_request')
            ->whereNull('markupable_type')
            ->whereNull('markupable_id')
            ->first();

        if ($markup) {
            \Log::debug('Found general rental request markup', ['markup' => $markup]);
            return ['type' => $markup->type, 'value' => $markup->value];
        }

        // Дефолтная наценка: фиксированная 100₽
        \Log::debug('Using default markup: fixed 100');
        return ['type' => 'fixed', 'value' => 100];
    }

    private function findMarkupForRentalRequest(string $markupableType, int $markupableId): ?array
    {
        $markup = PlatformMarkup::where('entity_type', 'rental_request')
            ->where('markupable_type', $markupableType)
            ->where('markupable_id', $markupableId)
            ->first();

        return $markup ? ['type' => $markup->type, 'value' => $markup->value] : null;
    }

    private function reverseApplyMarkup(float $customerPrice, array $markup, int $workingHours): float
    {
        if ($markup['type'] === 'fixed') {
            // Фиксированная наценка за час: price_lessor = price_customer - markup
            $markupValue = $markup['value'];
            $result = max(0, $customerPrice - $markupValue);

            \Log::debug('Fixed markup reversed', [
                'customer_price' => $customerPrice,
                'markup_value' => $markupValue,
                'lessor_price' => $result,
                'type' => 'fixed'
            ]);

            return $result;
        } else {
            // Процентная наценка: price_lessor = price_customer / (1 + markup/100)
            $markupPercent = $markup['value'] / 100;
            $result = $customerPrice / (1 + $markupPercent);

            \Log::debug('Percentage markup reversed', [
                'customer_price' => $customerPrice,
                'markup_percent' => $markupPercent,
                'lessor_price' => $result,
                'type' => 'percent'
            ]);

            return $result;
        }
    }

}
