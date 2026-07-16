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
                ->withCount(['responses as active_proposals_count' => function($query) {
                    $query->where('status', 'pending')->where('expires_at', '>', now());
                }]);

            // Фильтрация по статусу предложений арендодателя
            if ($request->has('my_proposals_filter')) {
                switch ($request->my_proposals_filter) {
                    case 'with_proposals':
                        $query->has('responses', '>', 0);
                        break;
                    case 'without_proposals':
                        $query->has('responses', '=', 0);
                        break;
                    case 'accepted_proposals':
                        $query->whereHas('responses', function($q) {
                            $q->where('lessor_id', auth()->id())->where('status', 'accepted');
                        });
                        break;
                }
            }

            // Фильтрация по категории
            if ($request->has('category_id') && $request->category_id) {
                $query->whereHas('items', function($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            // Фильтрация по локации
            if ($request->has('location_id') && $request->location_id) {
                $query->where('location_id', $request->location_id);
            }

            // Сортировка
            switch ($request->get('sort', 'newest')) {
                case 'budget':
                    $query->orderBy('total_budget', 'desc');
                    break;
                case 'proposals':
                    $query->orderBy('active_proposals_count', 'desc');
                    break;
                case 'my_activity':
                    $query->orderBy('my_proposals_count', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }

            $rentalRequests = $query->paginate(12);

            // 🔥 ИСПРАВЛЕНИЕ: Получаем аналитику с обработкой ошибок
            try {
                $analytics = $this->proposalService->getLessorProposalAnalytics(auth()->id());
            } catch (\Exception $e) {
                Log::error('Error getting proposal analytics: ' . $e->getMessage());
                $analytics = $this->getDefaultAnalytics();
            }

            return view('lessor.rental-requests.index', [
                'rentalRequests' => $rentalRequests,
                'analytics' => $analytics, // 🔥 Теперь переменная точно передается
                'categories' => Category::where('is_active', true)->get(),
                'locations' => Location::all(),
                'filters' => $request->all()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in Lessor RentalRequestController: ' . $e->getMessage());

            return view('lessor.rental-requests.index', [
                'rentalRequests' => collect([]),
                'analytics' => $this->getDefaultAnalytics(),
                'categories' => Category::where('is_active', true)->get(),
                'locations' => Location::all(),
                'filters' => $request->all()
            ]);
        }
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
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
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
