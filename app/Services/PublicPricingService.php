<?php
// app/Services/PublicPricingService.php

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\Equipment;
use App\Models\RentalRequestResponse;
use App\Models\User;

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
        // Базовый расчет цены
        $basePrice = $equipment->rentalTerms->first()?->price_per_hour ?? 1000;

        \Log::info("💵 Price calculation for {$equipment->title}: base={$basePrice}");

        // Если в заявке указан бюджет, ориентируемся на него
        $requestMaxRate = $request->max_hourly_rate ?? $request->hourly_rate ?? 0;

        if ($requestMaxRate > 0) {
            // Устанавливаем цену на 15% ниже максимального бюджета заявки
            $adjustedPrice = $requestMaxRate * 0.85;
            $finalPrice = max($basePrice, $adjustedPrice);
            \Log::info("📊 Adjusted price: {$finalPrice} (based on request max: {$requestMaxRate})");
            return $finalPrice;
        }

        $finalPrice = max($basePrice, 100);
        \Log::info("📊 Final price: {$finalPrice}");
        return $finalPrice;
    }

    private function calculatePriceAdjustment(Equipment $equipment, RentalRequest $request): float
    {
        // Здесь можно добавить логику корректировки цены
        // Например, на основе сезона, спроса, срочности и т.д.
        return 0;
    }
}
