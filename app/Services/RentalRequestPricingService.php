<?php

namespace App\Services;

use App\Models\PlatformMarkup;
use App\Models\RentalRequest;
use App\Models\RentalRequestItem;
use App\Models\EquipmentCategory;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RentalRequestPricingService
{

    protected $markupCalculationService;

    public function __construct(MarkupCalculationService $markupCalculationService)
    {
        $this->markupCalculationService = $markupCalculationService;
    }

    /**
     * Преобразует цены заявки для отображения арендодателям
     * (с обратным применением наценки — коммерческая тайна)
     *
     * Для каждой позиции учитываются индивидуальные условия аренды (часы/смены).
     */
    public function calculateLessorPrices(RentalRequest $request): array
    {
        $rentalDays = $this->calculateRentalDays($request);
        $lessorPrices = [];
        $totalLessorBudget = 0;
        $totalWorkingHours = 0;

        foreach ($request->items as $item) {
            // Цена арендатора за час (с наценкой)
            $customerHourlyRate = (float) ($item->hourly_rate ?? 0);

            // Получаем применимую наценку для этой заявки/категории
            $lesseeCompanyId = $request->user?->company_id;
            $markup = $this->markupCalculationService->findApplicableMarkup(
                'rental_request',
                null,
                $item->category_id,
                null,
                $lesseeCompanyId,
                $request->id // rentalRequestId — наценка на конкретную заявку
            );

            // Обратное применение наценки к часовой ставке (за 1 час)
            $lessorHourlyRate = $this->reverseApplyHourlyMarkup($customerHourlyRate, $markup);

            // Расчёт рабочих часов для этой позиции (с учётом её индивидуальных условий)
            $itemWorkingHours = $this->calculateWorkingHoursForItem($item, $request);
            $totalWorkingHours += $itemWorkingHours;

            // Общая сумма для арендодателя за весь период
            // lessorHourlyRate — уже с вычетом наценки, за 1 час
            $itemTotal = $lessorHourlyRate * $item->quantity * $itemWorkingHours;

            $lessorPrices[] = [
                'item_id' => $item->id,
                'category_id' => $item->category_id,
                'category_name' => $item->category->name ?? '',
                'quantity' => $item->quantity,
                'customer_hourly_rate' => $customerHourlyRate,
                'lessor_hourly_rate' => $lessorHourlyRate,
                'item_total' => $itemTotal,
                'working_hours' => $itemWorkingHours,
            ];

            $totalLessorBudget += $itemTotal;
        }

        return [
            'items' => $lessorPrices,
            'total_lessor_budget' => $totalLessorBudget,
            'working_hours' => $totalWorkingHours,
            'rental_days' => $rentalDays,
        ];
    }

    /**
     * Расчёт рабочих часов для конкретной позиции заявки
     * с учётом её индивидуальных условий (или общих).
     */
    private function calculateWorkingHoursForItem(RentalRequestItem $item, RentalRequest $request): int
    {
        $start = Carbon::parse($request->rental_period_start);
        $end = Carbon::parse($request->rental_period_end);
        $days = $start->diffInDays($end) + 1;

        // Берём effective_conditions из модели — там уже логика:
        // если use_individual_conditions и individual_conditions не пусты — берёт их,
        // иначе — берёт общие rental_conditions из заявки
        $conditions = $item->effective_conditions;

        $shiftHours = (int) ($conditions['hours_per_shift'] ?? 8);
        $shiftsPerDay = (int) ($conditions['shifts_per_day'] ?? 1);

        return $days * $shiftHours * $shiftsPerDay;
    }

    /**
     * Обратное применение наценки к часовой ставке.
     * Наценка и цена — за 1 час, поэтому не умножаем на workingHours.
     */
    private function reverseApplyHourlyMarkup(float $customerHourlyRate, array $markup): float
    {
        switch ($markup['type'] ?? 'fixed') {
            case 'fixed':
                // customer = lessor + markup → lessor = customer - markup
                return max(0, $customerHourlyRate - (float) ($markup['value'] ?? 0));

            case 'percent':
                // customer = lessor * (1 + markup%) → lessor = customer / (1 + markup%)
                $percent = (float) ($markup['value'] ?? 0);
                return $customerHourlyRate / (1 + $percent / 100);

            case 'combined':
                $fixedPart = (float) ($markup['rules']['fixed_value'] ?? 0);
                $percentValue = (float) ($markup['rules']['percent_value'] ?? 0);
                $afterFixed = max(0, $customerHourlyRate - $fixedPart);
                return $afterFixed / (1 + $percentValue / 100);

            default:
                return $customerHourlyRate;
        }
    }

    private function calculateRentalDays(RentalRequest $request): int
    {
        $start = Carbon::parse($request->rental_period_start);
        $end = Carbon::parse($request->rental_period_end);
        return $start->diffInDays($end) + 1;
    }

    /**
     * Расчет цены для создания предложения (когда арендодатель предлагает свою цену)
     */
    public function calculateProposalPrice(float $lessorProposedPrice, array $markup, int $workingHours): float
    {
        $markupResult = $this->markupCalculationService->calculateMarkup(
            $lessorProposedPrice,
            'proposal',
            $workingHours,
            null, null, null, null
        );

        return $markupResult['final_price'];
    }

    /**
     * Получить наценку для оборудования в контексте заявки
     */
    public function getMarkupForEquipment($equipment, $lesseeCompany): array
    {
        $basePrice = $equipment->rentalTerms->first()?->price_per_hour ?? 0;

        $markupResult = $this->markupCalculationService->calculateMarkup(
            $basePrice,
            'rental_request',
            1,
            $equipment->id,
            $equipment->category_id,
            null,
            $lesseeCompany?->id
        );

        return [
            'type' => $markupResult['markup_type'],
            'value' => $markupResult['markup_value'],
            'source' => $markupResult['calculation_details']['source']
        ];
    }
}
