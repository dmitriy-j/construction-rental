<?php

namespace App\Services;

use App\Models\PlatformMarkup;
use App\Models\RentalRequest;
use App\Models\EquipmentCategory;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class RentalRequestPricingService
{
    /**
     * Преобразует цены заявки для отображения арендодателям
     */
    public function calculateLessorPrices(RentalRequest $request): array
    {
        $workingHours = $this->calculateWorkingHours($request);
        $lessorPrices = [];
        $totalLessorBudget = 0;

        foreach ($request->items as $item) {
            $customerPrice = $item->hourly_rate;

            \Log::debug("📊 Processing request item", [
                'item_id' => $item->id,
                'category_id' => $item->category_id,
                'customer_price' => $customerPrice,
                'quantity' => $item->quantity
            ]);

            // Получаем наценку для заявки
            $markup = $this->getPlatformMarkupForRentalRequest($item->category_id, $request->user->company);
            $lessorPrice = $this->reverseApplyMarkup($customerPrice, $markup, $workingHours);

            $itemTotal = $lessorPrice * $item->quantity * $workingHours;

            $lessorPrices[] = [
                'item_id' => $item->id,
                'category_id' => $item->category_id,
                'category_name' => $item->category->name,
                'quantity' => $item->quantity,
                'specifications' => $item->specifications,
                'customer_price' => $customerPrice,
                'lessor_price' => $lessorPrice,
                'item_total' => $itemTotal,
                'markup_type' => $markup['type'],
                'markup_value' => $markup['value'],
                'working_hours' => $workingHours
            ];

            $totalLessorBudget += $itemTotal;
        }

        \Log::info("💰 Final lessor prices calculation", [
            'request_id' => $request->id,
            'items_count' => count($lessorPrices),
            'total_lessor_budget' => $totalLessorBudget
        ]);

        return [
            'items' => $lessorPrices,
            'total_lessor_budget' => $totalLessorBudget,
            'working_hours' => $workingHours,
            'rental_days' => $this->calculateRentalDays($request)
        ];
    }

    /**
     * Обратное применение наценки - из цены арендатора получаем цену для арендодателя
     */
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

    /**
     * Получаем наценку для заявки
     */
    private function getPlatformMarkupForRentalRequest(?int $categoryId, ?Company $lesseeCompany): array
    {
        \Log::debug("🔍 Getting platform markup", [
            'category_id' => $categoryId,
            'lessee_company_id' => $lesseeCompany?->id
        ]);

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

    private function calculateWorkingHours(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        $days = $start->diffInDays($end) + 1;

        $shiftHours = $request->rental_conditions['hours_per_shift'] ?? 8;
        $shiftsPerDay = $request->rental_conditions['shifts_per_day'] ?? 1;

        return $days * $shiftHours * $shiftsPerDay;
    }

    private function calculateRentalDays(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        return $start->diffInDays($end) + 1;
    }

    /**
     * Расчет цены для создания предложения (когда арендодатель предлагает свою цену)
     */
    public function calculateProposalPrice(float $lessorProposedPrice, array $markup, int $workingHours): float
    {
        // Прямое применение наценки
        if ($markup['type'] === 'fixed') {
            $markupValue = $markup['value']; // фиксированная наценка за час
            $result = $lessorProposedPrice + $markupValue;

            Log::debug('Proposal price calculation (fixed)', [
                'lessor_price' => $lessorProposedPrice,
                'markup' => $markupValue,
                'customer_price' => $result
            ]);

            return $result;
        } else {
            $markupPercent = $markup['value'] / 100;
            $result = $lessorProposedPrice * (1 + $markupPercent);

            Log::debug('Proposal price calculation (percent)', [
                'lessor_price' => $lessorProposedPrice,
                'markup_percent' => $markupPercent,
                'customer_price' => $result
            ]);

            return $result;
        }
    }

    /**
     * Получить наценку для оборудования в контексте заявки
     */
    public function getMarkupForEquipment($equipment, $lesseeCompany): array
    {
        return $this->getPlatformMarkupForRentalRequest(
            $equipment->category_id,
            $lesseeCompany
        );
    }
}
