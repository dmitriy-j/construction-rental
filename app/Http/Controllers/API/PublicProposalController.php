<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use App\Models\Equipment;
use App\Models\Company;
use App\Services\BulkProposalService;
use App\Services\ProposalManagementService;
use App\Services\PublicPricingService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicProposalController extends Controller
{
     public function __construct(
        private ProposalManagementService $proposalService,
        private BulkProposalService $bulkProposalService,
        private PublicPricingService $publicPricingService
    ) {
        $this->middleware(['auth:sanctum', 'company.verified']);
    }

    public function store(Request $request, $rentalRequestId): JsonResponse
    {
        \Log::debug('PROPOSAL STORE CALLED', [
            'rental_request_id' => $rentalRequestId,
            'has_items' => $request->has('equipment_items'),
            'items_count' => count($request->input('equipment_items', [])),
            'user_id' => auth()->id()
        ]);
        try {
            $validated = $request->validate([
                'equipment_items' => 'required|array|min:1',
                'equipment_items.*.equipment_id' => 'required|exists:equipment,id',
                'equipment_items.*.proposed_price' => 'required|numeric|min:100|max:100000',
                'equipment_items.*.quantity' => 'required|integer|min:1|max:100',
                'message' => 'required|string|min:10|max:1000'
            ]);

            $rentalRequest = RentalRequest::with(['items', 'user.company'])
                ->where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->findOrFail($rentalRequestId);

            $lessor = auth()->user();

            // Проверка на дубликаты
            foreach ($validated['equipment_items'] as $equipmentItem) {
                $existingProposal = RentalRequestResponse::where('rental_request_id', $rentalRequestId)
                    ->where('lessor_id', $lessor->id)
                    ->where('equipment_id', $equipmentItem['equipment_id'])
                    ->whereIn('status', ['pending', 'accepted'])
                    ->first();

                if ($existingProposal) {
                    return response()->json([
                        'success' => false,
                        'message' => "Вы уже отправили предложение по данному оборудованию."
                    ], 422);
                }
            }

            $workingHours = $this->calculateWorkingHours($rentalRequest);
            $proposals = [];
            $totalPrice = 0;

            foreach ($validated['equipment_items'] as $equipmentItem) {
                $equipment = Equipment::find($equipmentItem['equipment_id']);

                if (!$equipment || $equipment->company_id !== $lessor->company_id) {
                    throw new \Exception("Оборудование не принадлежит вашей компании");
                }

                // Цена, которую указал арендодатель (уже с вычетом наценки)
                $lessorPricePerUnit = (float) $equipmentItem['proposed_price'];
                $quantity = (int) ($equipmentItem['quantity'] ?? 1);

                // Наценка платформы для предложений
                $lesseeCompany = $rentalRequest->user?->company;
                $markup = $this->publicPricingService->getPlatformMarkupForRentalRequest(
                    $equipment->category_id,
                    $lesseeCompany,
                    $rentalRequest->id // rentalRequestId
                );

                // Цена для арендатора = цена арендодателя + наценка
                $customerPricePerUnit = $lessorPricePerUnit + (float) ($markup['value'] ?? 0);

                // Итоговая сумма за период
                $totalItemPrice = $customerPricePerUnit * $quantity * $workingHours;

                $priceBreakdown = [
                    'equipment_id' => $equipment->id,
                    'equipment_title' => $equipment->title,
                    'quantity' => $quantity,
                    'working_hours' => $workingHours,
                    'lessor_price_per_unit' => $lessorPricePerUnit,
                    'customer_price_per_unit' => $customerPricePerUnit,
                    'item_total_lessor' => $lessorPricePerUnit * $quantity * $workingHours,
                    'item_total_customer' => $totalItemPrice,
                ];

                $proposal = RentalRequestResponse::create([
                    'rental_request_id' => $rentalRequest->id,
                    'lessor_id' => $lessor->id,
                    'equipment_id' => $equipment->id,
                    'proposed_price' => $totalItemPrice,
                    'proposed_quantity' => $quantity,
                    'message' => $validated['message'],
                    'price_breakdown' => $priceBreakdown,
                    'status' => 'pending',
                    'expires_at' => now()->addDays(14)
                ]);

                $proposals[] = $proposal;
                $totalPrice += $totalItemPrice;
            }

            $rentalRequest->increment('responses_count');

            return response()->json([
                'success' => true,
                'message' => 'Предложение отправлено!',
                'data' => [
                    'proposals' => $proposals,
                    'total_price' => $totalPrice,
                    'items_count' => count($proposals),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating proposal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке предложения: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableEquipment($rentalRequestId): JsonResponse
    {
        \Log::info("🟢 Метод getAvailableEquipment вызван для заявки: " . $rentalRequestId);

        try {
            $rentalRequest = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->with(['items.category'])
                ->findOrFail($rentalRequestId);

            $lessor = auth()->user();

            if (!$lessor->company || !$lessor->company->is_lessor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Только арендодатели могут просматривать доступную технику'
                ], 403);
            }

            $requestCategoryIds = $rentalRequest->items->pluck('category_id')->filter()->unique()->toArray();

            if (empty($requestCategoryIds)) {
                return response()->json([
                    'success' => true,
                    'data' => ['available_equipment' => []]
                ]);
            }

            $companyEquipment = Equipment::where('company_id', $lessor->company_id)
                ->whereIn('category_id', $requestCategoryIds)
                ->where('is_approved', true)
                ->with(['category', 'rentalTerms'])
                ->get();

            if ($companyEquipment->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => ['available_equipment' => []]
                ]);
            }

            $equipmentWithPricing = [];

            foreach ($companyEquipment as $equipment) {
                try {
                    $matchingItem = $rentalRequest->items->first(function ($item) use ($equipment) {
                        return $item->category_id == $equipment->category_id;
                    });

                    if (!$matchingItem) continue;

                    $customerPrice = $matchingItem->hourly_rate ?? 0;

                    // Получаем наценку
                    $markup = $this->publicPricingService->getPlatformMarkupForRentalRequest(
                        $equipment->category_id,
                        $rentalRequest->user->company
                    );

                    // Цена для арендодателя = цена арендатора - наценка
                    $recommendedPrice = $this->reverseApplyMarkup($customerPrice, $markup);

                    $equipmentWithPricing[] = [
                        'equipment' => $equipment,
                        'recommended_lessor_price' => $recommendedPrice,
                        'max_available_quantity' => 1,
                        'working_hours' => $this->calculateWorkingHours($rentalRequest),
                        'customer_price' => $customerPrice,
                    ];

                } catch (\Exception $e) {
                    \Log::error("❌ Error processing equipment {$equipment->id}: " . $e->getMessage());
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'available_equipment' => $equipmentWithPricing
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Ошибка в getAvailableEquipment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки доступной техники: ' . $e->getMessage()
            ], 500);
        }
    }

    private function reverseApplyMarkup(float $customerPrice, array $markup): float
    {
        if ($markup['type'] === 'fixed') {
            return max(0, $customerPrice - $markup['value']);
        } else {
            return $customerPrice / (1 + ($markup['value'] / 100));
        }
    }

    private function calculateWorkingHours(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        $days = $start->diffInDays($end) + 1;

        // Берём индивидуальные условия из первой позиции, если есть
        $item = $request->items->first();
        if ($item) {
            $conditions = $item->effective_conditions;
        } else {
            $conditions = $request->rental_conditions ?? [];
        }

        $shiftHours = (int) ($conditions['hours_per_shift'] ?? 8);
        $shiftsPerDay = (int) ($conditions['shifts_per_day'] ?? 1);

        return $days * $shiftHours * $shiftsPerDay;
    }
}
