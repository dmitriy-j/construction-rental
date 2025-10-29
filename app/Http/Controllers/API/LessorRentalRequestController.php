<?php
// app/Http/Controllers/API/LessorRentalRequestController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Services\ProposalManagementService;
use App\Services\RentalRequestPricingService;
use Illuminate\Http\Request;

class LessorRentalRequestController extends Controller
{
    protected $proposalService;
    protected $pricingService;

    public function __construct(
        ProposalManagementService $proposalService,
        RentalRequestPricingService $pricingService
    ) {
        $this->proposalService = $proposalService;
        $this->pricingService = $pricingService;
    }

    public function index(Request $request)
    {
        try {
            $query = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->with([
                    'items.category',
                    'location',
                    'responses' => function($query) {
                        $query->where('lessor_id', auth()->id())
                              ->with('equipment');
                    }
                ])
                ->withCount(['responses as my_proposals_count' => function($query) {
                    $query->where('lessor_id', auth()->id());
                }])
                ->withCount(['responses as active_proposals_count']);

            // Применяем фильтры
            if ($request->has('my_proposals_filter')) {
                // ... логика фильтрации
            }

            $requests = $query->paginate(12);

            // Добавляем историю предложений для каждой заявки
            $requests->getCollection()->transform(function ($request) {
                $request->proposal_history = $this->proposalService->getProposalHistoryForRequest(
                    $request->id,
                    auth()->id()
                );

                // Добавляем цены для арендодателя
                $request->lessor_pricing = $this->pricingService->calculateLessorPrices($request);

                return $request;
            });

            $analytics = $this->proposalService->getLessorProposalAnalytics(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $requests,
                'analytics' => $analytics
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in LessorRentalRequestController: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки заявок'
            ], 500);
        }
    }

    public function filteredIndex(Request $request)
    {
        $query = RentalRequest::where('status', 'active')
            ->where('visibility', 'public')
            ->where('expires_at', '>', now());

        // Расширенная фильтрация
        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->has('budget_min')) {
            $query->where('total_budget', '>=', $request->budget_min);
        }

        // Добавить поддержку пагинации с фильтрами
        return response()->json([
            'success' => true,
            'data' => $query->paginate(12),
            'filters' => $request->all()
        ]);
    }
}
