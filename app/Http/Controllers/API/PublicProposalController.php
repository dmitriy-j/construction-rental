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

    /**
     * Создание предложения (одиночного или bulk)
     */
    public function store(Request $request, $rentalRequestId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'equipment_items' => 'required|array|min:1',
                'equipment_items.*.equipment_id' => 'required|exists:equipment,id',
                'equipment_items.*.proposed_price' => 'required|numeric|min:100|max:100000',
                'equipment_items.*.quantity' => 'required|integer|min:1|max:100',
                'message' => 'required|string|min:10|max:1000'
            ]);

            $rentalRequest = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->findOrFail($rentalRequestId);

            $lessor = auth()->user();

            // 🔥 ПРОВЕРКА НА ДУБЛИКАТЫ ПРЕЖДЕ ЧЕМ СОЗДАВАТЬ
            foreach ($validated['equipment_items'] as $equipmentItem) {
                $existingProposal = RentalRequestResponse::where('rental_request_id', $rentalRequestId)
                    ->where('lessor_id', $lessor->id)
                    ->where('equipment_id', $equipmentItem['equipment_id'])
                    ->whereIn('status', ['pending', 'accepted'])
                    ->first();

                if ($existingProposal) {
                    return response()->json([
                        'success' => false,
                        'message' => "Вы уже отправили предложение по оборудованию ID: {$equipmentItem['equipment_id']}. Вы можете отредактировать существующее предложение."
                    ], 422);
                }
            }

            // 🔥 ПРОВЕРКА ВОЗМОЖНОСТИ ДОСТАВКИ С ПОМОЩЬЮ ХЕЛПЕРА
            $deliveryFeasibility = \App\Helpers\ProposalDeliveryHelper::validateDeliveryFeasibility(
                $rentalRequest,
                $lessor->company
            );

            if (!$deliveryFeasibility['feasible']) {
                return response()->json([
                    'success' => false,
                    'message' => $deliveryFeasibility['error']
                ], 422);
            }

            $pricingService = app(PricingService::class);
            $proposals = [];
            $totalPrice = 0;

            // 🔥 РАСЧЕТ СТОИМОСТИ ДОСТАВКИ С ПОМОЩЬЮ ХЕЛПЕРА
            $deliveryCalculation = \App\Helpers\ProposalDeliveryHelper::calculateDelivery(
                $rentalRequest,
                $lessor->company,
                $validated['equipment_items']
            );

            foreach ($validated['equipment_items'] as $equipmentItem) {
                $equipment = Equipment::find($equipmentItem['equipment_id']);

                if (!$equipment || $equipment->company_id !== $lessor->company_id) {
                    throw new \Exception("Оборудование не принадлежит вашей компании");
                }

                $workingHours = $this->calculateWorkingHours($rentalRequest);

                $priceCalculation = $pricingService->calculateProposalPrice(
                    $equipment,
                    $rentalRequest,
                    $equipmentItem['proposed_price'],
                    $workingHours
                );

                $markupDetails = $priceCalculation['markup_details'] ?? ['type' => 'fixed', 'value' => 100];

                // 🔥 РАСПРЕДЕЛЕНИЕ СТОИМОСТИ ДОСТАВКИ МЕЖДУ ПОЗИЦИЯМИ
                $deliveryCostPerItem = 0;
                if ($deliveryCalculation['delivery_required']) {
                    $totalItems = count($validated['equipment_items']);
                    $deliveryCostPerItem = $deliveryCalculation['delivery_cost'] / $totalItems;
                }

                $priceBreakdown = [
                    'equipment_id' => $equipment->id,
                    'equipment_title' => $equipment->title,
                    'quantity' => $equipmentItem['quantity'],
                    'working_hours' => $workingHours,
                    'lessor_price_per_unit' => $equipmentItem['proposed_price'],
                    'customer_price_per_unit' => $priceCalculation['price_per_hour'],
                    'platform_fee_per_unit' => $priceCalculation['platform_fee'] / max(1, $workingHours),
                    'item_total_lessor' => $equipmentItem['proposed_price'] * $workingHours * $equipmentItem['quantity'],
                    'item_total_customer' => $priceCalculation['calculated_price'],
                    'total_platform_fee' => $priceCalculation['platform_fee'],
                    'markup_details' => $markupDetails,
                    'delivery_breakdown' => [
                        'delivery_required' => $deliveryCalculation['delivery_required'],
                        'delivery_cost' => $deliveryCostPerItem,
                        'total_delivery_cost' => $deliveryCalculation['delivery_cost'],
                        'distance_km' => $deliveryCalculation['distance_km'] ?? 0,
                        'vehicle_type' => $deliveryCalculation['vehicle_type'] ?? null,
                        'rate_per_km' => $deliveryCalculation['rate_per_km'] ?? 0,
                        'from_location' => $deliveryCalculation['from_location'] ?? null,
                        'to_location' => $deliveryCalculation['to_location'] ?? null,
                    ],
                    'calculation_details' => [
                        'rental_days' => $this->calculateRentalDays($rentalRequest),
                        'hours_per_day' => ($rentalRequest->rental_conditions['hours_per_shift'] ?? 8) *
                                        ($rentalRequest->rental_conditions['shifts_per_day'] ?? 1)
                    ]
                ];

                // 🔥 ОБЩАЯ СТОИМОСТЬ С ДОСТАВКОЙ
                $totalItemPrice = $priceBreakdown['item_total_customer'] + $deliveryCostPerItem;

                $proposal = RentalRequestResponse::create([
                    'rental_request_id' => $rentalRequest->id,
                    'lessor_id' => $lessor->id,
                    'equipment_id' => $equipment->id,
                    'proposed_price' => $totalItemPrice,
                    'proposed_quantity' => $equipmentItem['quantity'],
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
                'message' => count($proposals) > 1 ? 'Комплексное предложение отправлено!' : 'Предложение отправлено!',
                'data' => [
                    'proposals' => $proposals,
                    'total_price' => $totalPrice,
                    'total_delivery_cost' => $deliveryCalculation['delivery_cost'],
                    'items_count' => count($proposals),
                    'delivery_required' => $deliveryCalculation['delivery_required'],
                    'delivery_cost' => $deliveryCalculation['delivery_cost']
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

    //Временный метод для проверки локации, удалить
    public function debugLocations($rentalRequestId): JsonResponse
    {
        try {
            $rentalRequest = RentalRequest::findOrFail($rentalRequestId);
            $lessor = auth()->user();

            $customerLocation = Location::find($rentalRequest->location_id);
            $lessorLocation = Location::where('company_id', $lessor->company_id)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'customer_location' => $customerLocation,
                    'lessor_location' => $lessorLocation,
                    'has_coordinates' => [
                        'customer' => $customerLocation && $customerLocation->latitude && $customerLocation->longitude,
                        'lessor' => $lessorLocation && $lessorLocation->latitude && $lessorLocation->longitude
                    ],
                    'distance_calculation_possible' => $customerLocation && $lessorLocation
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //временный метод, удалить
    public function debugEquipmentLocations($rentalRequestId): JsonResponse
    {
        try {
            $rentalRequest = RentalRequest::findOrFail($rentalRequestId);
            $lessor = auth()->user();

            $equipmentList = Equipment::where('company_id', $lessor->company_id)
                ->with('location')
                ->get()
                ->map(function ($equipment) {
                    return [
                        'id' => $equipment->id,
                        'title' => $equipment->title,
                        'location' => $equipment->location ? [
                            'id' => $equipment->location->id,
                            'name' => $equipment->location->name,
                            'address' => $equipment->location->address
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'equipment_count' => $equipmentList->count(),
                    'equipment' => $equipmentList
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function calculateDelivery(Request $request, $rentalRequestId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'equipment_items' => 'required|array|min:1',
                'equipment_items.*.equipment_id' => 'required|exists:equipment,id',
                'equipment_items.*.quantity' => 'required|integer|min:1'
            ]);

            $rentalRequest = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->findOrFail($rentalRequestId);

            $lessor = auth()->user();

            $deliveryCalculation = \App\Helpers\ProposalDeliveryHelper::calculateDelivery(
                $rentalRequest,
                $lessor->company,
                $validated['equipment_items']
            );

            return response()->json([
                'success' => true,
                'data' => $deliveryCalculation
            ]);

        } catch (\Exception $e) {
            \Log::error('Error calculating delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка расчета доставки: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateRentalDays(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        return $start->diffInDays($end) + 1;
    }

    /**
     * Получение предложений с фильтрацией по стоимости доставки
     */
    public function getProposalsWithDelivery($rentalRequestId): JsonResponse
    {
        try {
            $rentalRequest = RentalRequest::findOrFail($rentalRequestId);
            $user = auth()->user();

            // Только владелец заявки может видеть предложения
            if ($rentalRequest->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен'
                ], 403);
            }

            $proposals = RentalRequestResponse::with(['lessor.company', 'equipment', 'equipment.category'])
                ->where('rental_request_id', $rentalRequestId)
                ->where('status', 'pending')
                ->get()
                ->map(function ($proposal) {
                    $priceBreakdown = $proposal->price_breakdown;
                    if (is_string($priceBreakdown)) {
                        $priceBreakdown = json_decode($priceBreakdown, true);
                    }

                    $deliveryCost = $priceBreakdown['delivery_breakdown']['delivery_cost'] ?? 0;
                    $basePrice = $priceBreakdown['item_total_customer'] ?? $proposal->proposed_price;

                    return [
                        'id' => $proposal->id,
                        'equipment' => $proposal->equipment,
                        'lessor' => $proposal->lessor,
                        'proposed_price' => $proposal->proposed_price,
                        'base_price' => $basePrice,
                        'delivery_cost' => $deliveryCost,
                        'total_price' => $basePrice + $deliveryCost,
                        'delivery_breakdown' => $priceBreakdown['delivery_breakdown'] ?? null,
                        'has_delivery' => !empty($priceBreakdown['delivery_breakdown']['delivery_required']),
                        'distance_km' => $priceBreakdown['delivery_breakdown']['distance_km'] ?? 0,
                        'message' => $proposal->message,
                        'created_at' => $proposal->created_at
                    ];
                });

            // Сортировка по общей стоимости (аренда + доставка)
            $sortedProposals = $proposals->sortBy('total_price')->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'proposals' => $sortedProposals,
                    'stats' => [
                        'total_proposals' => $proposals->count(),
                        'with_delivery' => $proposals->where('has_delivery', true)->count(),
                        'avg_delivery_cost' => $proposals->where('has_delivery', true)->avg('delivery_cost'),
                        'min_total_price' => $sortedProposals->first()['total_price'] ?? 0,
                        'max_total_price' => $sortedProposals->last()['total_price'] ?? 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching proposals with delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки предложений'
            ], 500);
        }
    }

    public function createComment(Request $request, $rentalRequestId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|min:10|max:1000',
                'type' => 'sometimes|in:question,clarification,other'
            ]);

            $rentalRequest = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->findOrFail($rentalRequestId);

            $lessor = auth()->user();

            // 🔥 СОЗДАЕМ КОММЕНТАРИЙ, НЕ ПРЕДЛОЖЕНИЕ
            $comment = RentalRequestResponse::create([
                'rental_request_id' => $rentalRequest->id,
                'lessor_id' => $lessor->id,
                'equipment_id' => null, // Явно null - это комментарий
                'proposed_price' => 0, // Цена = 0 для комментариев
                'proposed_quantity' => 0, // Количество = 0
                'message' => $validated['message'],
                'price_breakdown' => ['type' => 'comment'],
                'status' => 'comment', // 🔥 НОВЫЙ СТАТУС для комментариев
                'is_bulk_main' => false,
                'is_bulk_item' => false,
                'expires_at' => now()->addDays(14)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Комментарий успешно отправлен',
                'data' => [
                    'comment_id' => $comment->id,
                    'type' => 'comment'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке комментария'
            ], 500);
        }
    }

    /**
     * Получение доступной техники для заявки
     */
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

            \Log::info("📋 Request items:", [
                'items_count' => $rentalRequest->items->count(),
                'items' => $rentalRequest->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'category_id' => $item->category_id,
                        'category_name' => $item->category->name ?? 'Unknown',
                        'hourly_rate' => $item->hourly_rate,
                        'quantity' => $item->quantity
                    ];
                })
            ]);

            // Получаем уникальные категории из заявки
            $requestCategoryIds = $rentalRequest->items->pluck('category_id')->filter()->unique()->toArray();

            \Log::info("📋 Request categories:", [
                'category_ids' => $requestCategoryIds,
                'lessor_company_id' => $lessor->company_id
            ]);

            if (empty($requestCategoryIds)) {
                \Log::warning("❌ No category IDs found in request");
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

            \Log::info("🏗️ Found equipment:", [
                'equipment_count' => $companyEquipment->count(),
                'equipment_titles' => $companyEquipment->pluck('title')
            ]);

            if ($companyEquipment->isEmpty()) {
                \Log::warning("❌ No equipment found for categories", [
                    'categories' => $requestCategoryIds,
                    'company_id' => $lessor->company_id
                ]);

                return response()->json([
                    'success' => true,
                    'data' => ['available_equipment' => []]
                ]);
            }

            $equipmentWithPricing = [];

            foreach ($companyEquipment as $equipment) {
                \Log::info("💰 Processing equipment: " . $equipment->title, [
                    'category_id' => $equipment->category_id,
                    'category_name' => $equipment->category->name ?? 'Unknown'
                ]);

                try {
                    // Находим соответствующую позицию в заявке
                    $matchingItem = $rentalRequest->items->first(function ($item) use ($equipment) {
                        return $item->category_id == $equipment->category_id;
                    });

                    if (!$matchingItem) {
                        \Log::warning("❌ No matching item found for equipment", [
                            'equipment' => $equipment->title,
                            'equipment_category_id' => $equipment->category_id,
                            'request_categories' => $rentalRequest->items->pluck('category_id')
                        ]);
                        continue;
                    }

                    $customerPrice = $matchingItem->hourly_rate ?? 0;
                    \Log::info("📊 Customer price from request: {$customerPrice}");

                    // 🔥 ИСПРАВЛЕНИЕ: Используем публичный метод сервиса
                    $markup = $this->publicPricingService->getPlatformMarkupForRentalRequest(
                        $equipment->category_id,
                        $rentalRequest->user->company
                    );

                    \Log::info("🏷️ Platform markup:", $markup);

                    // Рассчитываем рекомендуемую цену для арендодателя
                    $recommendedPrice = $this->reverseApplyMarkup($customerPrice, $markup);

                    \Log::info("🎯 Final recommended price: {$recommendedPrice} (from customer: {$customerPrice})");

                    $equipmentWithPricing[] = [
                        'equipment' => $equipment,
                        'recommended_lessor_price' => $recommendedPrice,
                        'max_available_quantity' => $this->getMaxAvailableQuantity($equipment),
                        'working_hours' => $this->calculateWorkingHours($rentalRequest),
                        'customer_price' => $customerPrice,
                        'markup_details' => $markup
                    ];

                } catch (\Exception $e) {
                    \Log::error("❌ Error processing equipment {$equipment->id}: " . $e->getMessage());
                    continue;
                }
            }

            \Log::info("✅ Final equipment with pricing:", [
                'count' => count($equipmentWithPricing)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'available_equipment' => $equipmentWithPricing
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Ошибка в getAvailableEquipment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_id' => $rentalRequestId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки доступной техники: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getMaxAvailableQuantity(Equipment $equipment): int
    {
        // Здесь можно добавить логику проверки доступности
        // Пока возвращаем 1 как базовое значение
        return 1;
    }

    /**
     * Вспомогательный метод для обратного применения наценки
     */
    private function reverseApplyMarkup(float $customerPrice, array $markup): float
    {
        if ($markup['type'] === 'fixed') {
            $result = max(0, $customerPrice - $markup['value']);

            \Log::debug('Fixed markup reversed', [
                'customer_price' => $customerPrice,
                'markup_value' => $markup['value'],
                'lessor_price' => $result
            ]);

            return $result;
        } else {
            $markupPercent = $markup['value'] / 100;
            $result = $customerPrice / (1 + $markupPercent);

            \Log::debug('Percentage markup reversed', [
                'customer_price' => $customerPrice,
                'markup_percent' => $markupPercent,
                'lessor_price' => $result
            ]);

            return $result;
        }
    }

    /**
     * Вспомогательный метод для получения наценки
     */
    private function getPlatformMarkup($equipment, $lesseeCompany, $workingHours): array
    {
        try {
            // Используем существующий сервис для получения наценки
            if (method_exists($this->publicPricingService, 'getPlatformMarkupForRentalRequest')) {
                return $this->publicPricingService->getPlatformMarkupForRentalRequest(
                    $equipment->category_id,
                    $lesseeCompany
                );
            }

            // Fallback: упрощенная версия - всегда фиксированная наценка 100₽
            \Log::debug('Using fallback markup calculation');
            return ['type' => 'fixed', 'value' => 100];

        } catch (\Exception $e) {
            \Log::warning('Error getting platform markup, using default: ' . $e->getMessage());
            return ['type' => 'fixed', 'value' => 100];
        }
    }

    /**
     * Вспомогательный метод для расчета рабочих часов
     */
    private function calculateWorkingHours(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        $days = $start->diffInDays($end) + 1;

        $shiftHours = $request->rental_conditions['hours_per_shift'] ?? 8;
        $shiftsPerDay = $request->rental_conditions['shifts_per_day'] ?? 1;

        return $days * $shiftHours * $shiftsPerDay;
    }

    /**
     * ✅ НОВЫЙ МЕТОД: Получение деталей bulk-предложения
     */
    public function getBulkProposal($proposalId): JsonResponse
    {
        try {
            $proposal = $this->bulkProposalService->getBulkProposalWithItems($proposalId);

            // Проверяем права доступа
            if (auth()->id() !== $proposal->lessor_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'proposal' => $proposal,
                    'items' => $proposal->bulkItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'equipment' => $item->equipment,
                            'proposed_price' => $item->proposed_price,
                            'quantity' => $item->proposed_quantity,
                            'price_breakdown' => $item->price_breakdown
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching bulk proposal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки предложения'
            ], 500);
        }
    }

    /**
     * ✅ НОВЫЙ МЕТОД: Принятие bulk-предложения арендатором
     */
    public function acceptBulkProposal($proposalId): JsonResponse
    {
        try {
            $proposal = $this->bulkProposalService->getBulkProposalWithItems($proposalId);

            // Проверяем права доступа (только арендатор, создавший заявку)
            if (auth()->id() !== $proposal->rentalRequest->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Доступ запрещен'
                ], 403);
            }

            $order = $this->bulkProposalService->createOrderFromBulkProposal($proposal);

            return response()->json([
                'success' => true,
                'message' => 'Bulk-предложение принято, заказ создан',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->company_order_number
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error accepting bulk proposal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при принятии предложения: ' . $e->getMessage()
            ], 500);
        }
    }
}
