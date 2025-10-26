<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Services\RequestMatchingService;
use App\Services\RentalRequestService;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckCompanyVerified; // ПРАВИЛЬНЫЙ ИМПОРТ
use App\Http\Middleware\CheckUserType;

class RentalRequestSearchController extends Controller
{
    public function __construct(
        private RentalRequestService $rentalRequestService,
        private RequestMatchingService $matchingService
    ) {
        $this->middleware('auth');
        $this->middleware(CheckCompanyVerified::class); // ПРАВИЛЬНОЕ ИСПОЛЬЗОВАНИЕ
        $this->middleware(CheckUserType::class . ':lessor');
    }

    /**
     * Поиск релевантных заявок для арендодателя
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category_id', 'location_id', 'budget_max', 'sort_by']);

        // ИСПРАВЛЕНИЕ: Добавляем пагинацию вместо get()
        $perPage = $request->get('per_page', 15);

        $rentalRequests = $this->rentalRequestService->getActiveRequestsForLessor(
            auth()->user(),
            $filters,
            $perPage // Передаем параметр пагинации
        );

        // Получаем рекомендуемые заявки на основе оборудования арендодателя
        $recommendedRequests = $this->matchingService->getRecommendedRequests(auth()->user());

        if ($request->wantsJson()) {
            return response()->json([
                'requests' => $rentalRequests,
                'recommended' => $recommendedRequests,
                'filters' => $filters,
                'pagination' => [
                    'total' => $rentalRequests->total(),
                    'per_page' => $rentalRequests->perPage(),
                    'current_page' => $rentalRequests->currentPage(),
                    'last_page' => $rentalRequests->lastPage(),
                ]
            ]);
        }

        $categories = \App\Models\Category::active()->get();
        $locations = \App\Models\Location::all();

        return view('lessor.rental-requests.index', compact(
            'rentalRequests',
            'recommendedRequests',
            'categories',
            'locations',
            'filters'
        ));
    }

    /**
     * Просмотр деталей заявки
     */
    public function show($id)
    {
        $rentalRequest = RentalRequest::with([
            'category',
            'location',
            'user.company',
            'responses' => function ($query) {
                $query->where('lessor_id', '!=', auth()->id()) // Исключаем свои предложения
                      ->with('lessor.company');
            }
        ])->active()->findOrFail($id);

        // Проверяем, может ли арендодатель предложить технику для этой заявки
        $availableEquipment = $this->getAvailableEquipmentForRequest($rentalRequest);
        $hasExistingProposal = $rentalRequest->responses()
            ->where('lessor_id', auth()->id())
            ->exists();

        // Расчет примерной стоимости через PricingService
        $pricingService = app(\App\Services\PricingService::class);
        $priceExamples = [];

        foreach ($availableEquipment->take(3) as $equipment) {
            $priceExamples[$equipment->id] = $pricingService->calculateProposalPrice(
                $equipment,
                $rentalRequest
            );
        }

        // Увеличиваем счетчик просмотров
        $rentalRequest->increment('views_count');

        return view('lessor.rental-requests.show', compact(
            'rentalRequest',
            'availableEquipment',
            'hasExistingProposal',
            'priceExamples'
        ));
    }

    /**
     * Получение доступного оборудования для заявки
     */
    private function getAvailableEquipmentForRequest(RentalRequest $request)
    {
        return auth()->user()->company->equipment()
            ->where('category_id', $request->category_id)
            ->where('is_active', true)
            ->with(['images', 'rentalTerms', 'locations'])
            ->get()
            ->filter(function ($equipment) use ($request) {
                // Проверка доступности на период заявки
                $availabilityService = app(\App\Services\EquipmentAvailabilityService::class);
                return $availabilityService->isAvailableForPeriod(
                    $equipment->id,
                    $request->rental_period_start,
                    $request->rental_period_end
                );
            });
    }
}
