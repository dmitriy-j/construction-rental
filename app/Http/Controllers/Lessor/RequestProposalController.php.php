<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\Equipment;
use App\Services\ProposalManagementService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RequestProposalController extends Controller
{
    public function __construct(
        private ProposalManagementService $proposalService,
        private PricingService $pricingService
    ) {
        $this->middleware('auth');
        $this->middleware('check.company.verified');
        $this->middleware('check.user.type:lessor');
    }

    /**
     * Форма отправки предложения
     */
    public function create($requestId)
    {
        $rentalRequest = RentalRequest::active()->findOrFail($requestId);

        // Проверяем, что арендодатель еще не отправлял предложение
        $existingProposal = $rentalRequest->responses()
            ->where('lessor_id', auth()->id())
            ->first();

        if ($existingProposal) {
            return redirect()->route('lessor.rental-requests.show', $requestId)
                ->with('warning', 'Вы уже отправили предложение по этой заявке');
        }

        $availableEquipment = $this->getAvailableEquipmentForRequest($rentalRequest);

        if ($availableEquipment->isEmpty()) {
            return redirect()->route('lessor.rental-requests.index')
                ->with('error', 'У вас нет подходящего оборудования для этой заявки');
        }

        return view('lessor.rental_requests.proposal_create', compact(
            'rentalRequest',
            'availableEquipment'
        ));
    }

    /**
     * Отправка предложения
     */
    public function store(Request $request, $requestId): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'proposed_price' => 'required|numeric|min:0',
            'message' => 'required|string|min:10|max:1000',
            'availability_dates' => 'nullable|array',
            'additional_terms' => 'nullable|string|max:2000'
        ]);

        try {
            $rentalRequest = RentalRequest::active()->findOrFail($requestId);
            $equipment = Equipment::findOrFail($validated['equipment_id']);

            // Проверяем, что оборудование принадлежит арендодателю
            if ($equipment->company_id !== auth()->user()->company_id) {
                throw new \Exception('Оборудование не принадлежит вашей компании');
            }

            // Проверяем, что предложение не было отправлено ранее
            $existingProposal = $rentalRequest->responses()
                ->where('lessor_id', auth()->id())
                ->where('equipment_id', $equipment->id)
                ->exists();

            if ($existingProposal) {
                throw new \Exception('Вы уже отправляли предложение по этому оборудованию');
            }

            $proposal = $this->proposalService->createProposal(
                $rentalRequest,
                auth()->user(),
                $equipment,
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Предложение успешно отправлено',
                'proposal_id' => $proposal->id,
                'redirect_url' => route('lessor.rental-requests.show', $requestId)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке предложения: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Расчет рекомендуемой цены для предложения
     */
    public function calculatePrice($requestId, $equipmentId): JsonResponse
    {
        try {
            $rentalRequest = RentalRequest::findOrFail($requestId);
            $equipment = Equipment::findOrFail($equipmentId);

            // Проверка прав на оборудование
            if ($equipment->company_id !== auth()->user()->company_id) {
                throw new \Exception('Оборудование не принадлежит вашей компании');
            }

            $priceAnalysis = $this->pricingService->calculateProposalPrice(
                $equipment,
                $rentalRequest
            );

            return response()->json([
                'success' => true,
                'price_analysis' => $priceAnalysis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка расчета цены: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение доступного оборудования
     */
    private function getAvailableEquipmentForRequest(RentalRequest $request)
    {
        return auth()->user()->company->equipment()
            ->where('category_id', $request->category_id)
            ->where('is_active', true)
            ->with(['images', 'rentalTerms', 'locations'])
            ->get()
            ->filter(function ($equipment) use ($request) {
                $availabilityService = app(\App\Services\EquipmentAvailabilityService::class);
                return $availabilityService->isAvailableForPeriod(
                    $equipment->id,
                    $request->rental_period_start,
                    $request->rental_period_end
                );
            });
    }
}
