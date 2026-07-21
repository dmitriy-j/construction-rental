<?php
// app/Http/Controllers/Lessor/RentalRequestController.php
namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Http\Resources\RentalRequestResource;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestPricingService;
use App\Services\ProposalManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RentalRequestController extends Controller
{
    protected $pricingService;
    protected $proposalService;

    public function __construct(
        RentalRequestPricingService $pricingService,
        ProposalManagementService $proposalService
    ) {
        $this->pricingService = $pricingService;
        $this->proposalService = $proposalService;
    }

    public function index(Request $request)
    {
        // Перенаправляем на унифицированную страницу /requests
        return redirect()->route('rental-requests.index');
    }

    // 🔥 ДОБАВЛЕННО: Метод для получения аналитики по умолчанию
    private function getDefaultAnalytics()
    {
        return [
            'total_proposals' => 0,
            'accepted_proposals' => 0,
            'pending_proposals' => 0,
            'rejected_proposals' => 0,
            'conversion_rate' => 0,
            'total_revenue' => 0,
            'recent_activity' => []
        ];
    }

    public function show($id)
    {
        try {
            $rentalRequest = RentalRequest::where('status', 'active')
                ->where('expires_at', '>', now())
                ->visibleToLessor(auth()->user())
                ->with([
                    'items.category',
                    'location',
                    'responses' => function($query) {
                        $query->where('lessor_id', auth()->id())
                            ->with(['equipment', 'bulkItems.equipment']);
                    }
                ])
                ->withCount(['responses as total_proposals_count'])
                ->withCount(['responses as my_proposals_count' => function($query) {
                    $query->where('lessor_id', auth()->id());
                }])
                ->findOrFail($id);

            // Расширенная аналитика по этой заявке
            $proposalAnalytics = $this->proposalService->getRequestProposalAnalytics($id, auth()->id());
            $lessorPricing = $this->pricingService->calculateLessorPrices($rentalRequest);

            // 🔥 ДОБАВЛЕНО: Загружаем шаблоны для этой категории заявки
            $categoryIds = $rentalRequest->items->pluck('category_id')->unique()->toArray();
            $templates = \App\Models\ProposalTemplate::where('user_id', auth()->id())
                ->whereIn('category_id', $categoryIds)
                ->where('is_active', true)
                ->with('category')
                ->orderBy('usage_count', 'desc')
                ->get();

            // Скрываем данные арендатора от арендодателя
            $rentalRequest->makeHidden([
                'user_id',
                'company_id',
                'user',
                'company',
                'hourly_rate',
                'total_budget',
                'calculated_budget_from',
                'calculated_budget_to',
                'budget_from',
                'budget_to',
                'max_hourly_rate',
            ]);

            // Добавляем только название компании арендатора
            $lesseeCompanyName = $rentalRequest->user?->company?->legal_name;
            $rentalRequest->lessee_company_name = $lesseeCompanyName;

            return view('lessor.rental-requests.show', [
                'request' => $rentalRequest,
                'analytics' => $proposalAnalytics,
                'lessorPricing' => $lessorPricing,
                'proposalHistory' => $this->proposalService->getProposalHistoryForRequest($id, auth()->id()),
                'templates' => $templates, // 🔥 ДОБАВЛЕНО: Передаем шаблоны в представление
                'categories' => Category::where('is_active', true)->get() // 🔥 ДОБАВЛЕНО: Для создания новых шаблонов
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing rental request: ' . $e->getMessage());
            abort(404, 'Заявка не найдена или недоступна');
        }
    }
}
