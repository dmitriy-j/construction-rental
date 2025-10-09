<?php
// app/Http/Controllers/API/PublicProposalController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use App\Models\Equipment;
use App\Services\RentalRequestPricingService;
use App\Services\ProposalManagementService;
use App\Services\PublicPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicProposalController extends Controller
{
    public function __construct(
        private ProposalManagementService $proposalService,
        private RentalRequestPricingService $pricingService,
        private PublicPricingService $publicPricingService
    ) {
        $this->middleware(['auth:sanctum', 'company.verified']);
    }

    /**
     * Создание предложения по публичной заявке
     */
    public function store(Request $request, $rentalRequestId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'equipment_ids' => 'required|array|min:1',
                'equipment_ids.*' => 'exists:equipment,id',
                'proposed_prices' => 'required|array',
                'proposed_prices.*' => 'numeric|min:100',
                'quantities' => 'required|array',
                'quantities.*' => 'integer|min:1',
                'message' => 'required|string|min:10|max:1000'
            ]);

            $rentalRequest = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->findOrFail($rentalRequestId);

            $lessor = auth()->user();
            $workingHours = $this->pricingService->calculateWorkingHours($rentalRequest);

            // Расчет финальной цены для арендатора с наценкой
            $equipment = Equipment::find($validated['equipment_ids'][0]);
            $markup = $this->pricingService->getMarkupForEquipment($equipment, $rentalRequest->user->company);

            $lessorProposedPrice = $validated['proposed_prices'][0];
            $customerPrice = $this->pricingService->calculateProposalPrice(
                $lessorProposedPrice,
                $markup,
                $workingHours
            );

            // Создание предложения
            $proposal = RentalRequestResponse::create([
                'rental_request_id' => $rentalRequest->id,
                'lessor_id' => $lessor->id,
                'equipment_id' => $validated['equipment_ids'][0],
                'proposed_price' => $lessorProposedPrice, // Цена арендодателя
                'proposed_quantity' => $validated['quantities'][0],
                'message' => $validated['message'],
                'price_breakdown' => [
                    'lessor_price' => $lessorProposedPrice,
                    'customer_price' => $customerPrice,
                    'platform_markup' => $markup,
                    'working_hours' => $workingHours
                ],
                'status' => 'pending',
                'expires_at' => now()->addDays(14)
            ]);

            $rentalRequest->increment('responses_count');

            return response()->json([
                'success' => true,
                'message' => 'Предложение успешно отправлено',
                'data' => [
                    'proposal_id' => $proposal->id,
                    'lessor_price' => $lessorProposedPrice,
                    'calculated_customer_price' => $customerPrice
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating public proposal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке предложения: ' . $e->getMessage()
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
                ->findOrFail($rentalRequestId);

            $lessor = auth()->user();

            if (!$lessor->company || !$lessor->company->is_lessor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Только арендодатели могут просматривать доступную технику'
                ], 403);
            }

            // ДЕТАЛЬНОЕ ЛОГИРОВАНИЕ
            \Log::debug("🔧 Вызов publicPricingService->getAvailableEquipmentWithPricing", [
                'request_id' => $rentalRequestId,
                'lessor_id' => $lessor->id,
                'company_id' => $lessor->company_id
            ]);

            $equipmentData = $this->publicPricingService->getAvailableEquipmentWithPricing(
                $rentalRequest,
                $lessor
            );

            \Log::info("✅ Доступная техника загружена", [
                'request_id' => $rentalRequestId,
                'equipment_count' => count($equipmentData['available_equipment'] ?? [])
            ]);

            // ЛОГИРУЕМ СТРУКТУРУ ДАННЫХ
            \Log::debug("📦 Структура equipmentData", [
                'has_available_equipment' => isset($equipmentData['available_equipment']),
                'equipment_count' => count($equipmentData['available_equipment'] ?? []),
                'equipment_ids' => array_map(function($item) {
                    return $item['equipment']['id'] ?? 'unknown';
                }, $equipmentData['available_equipment'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'data' => $equipmentData
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Ошибка в getAvailableEquipment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_id' => $rentalRequestId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки доступной техники: ' . $e->getMessage()
            ], 500);
        }
    }
}
