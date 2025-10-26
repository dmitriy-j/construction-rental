<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestService;
use App\Services\RequestMatchingService;

class RentalRequestController extends Controller
{
    private $rentalRequestService;
    private $matchingService;

    public function __construct(
        RentalRequestService $rentalRequestService,
        RequestMatchingService $matchingService
    ) {
        $this->rentalRequestService = $rentalRequestService;
        $this->matchingService = $matchingService;
    }

    /**
     * Display a listing of the rental requests.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // ДЕБАГ: Проверим что мы знаем о пользователе
        \Log::debug('User Auth Debug', [
            'user_id' => $user ? $user->id : 'null',
            'authenticated' => Auth::check(),
            'company' => $user && $user->company ? $user->company->toArray() : 'no company',
            'is_lessor' => $user && $user->company ? $user->company->is_lessor : 'unknown',
            'user_company_relation_loaded' => $user && $user->relationLoaded('company')
        ]);

        // ДОБАВЛЕНО: Загружаем категории и локации для фильтров
        $categories = \App\Models\Category::where('is_active', true)->get();
        $locations = \App\Models\Location::all();

        // Для авторизованных арендодателей
        if ($user && $user->company && $user->company->is_lessor) {
            \Log::debug('User is recognized as LESSOR');
            $rentalRequests = $this->getLessorRequests($request);
            $recommendedRequests = $this->getRecommendedRequests($user);
        } else {
            \Log::debug('User is recognized as GUEST or LESSEE');
            $rentalRequests = $this->getPublicRequests($request);
            $recommendedRequests = collect();
        }

        // ДОБАВЛЕНО: Логирование для отладки
        \Log::debug('Final data for view', [
            'user_role' => $user && $user->company && $user->company->is_lessor ? 'lessor' : 'guest',
            'requests_count' => $rentalRequests->count(),
            'categories_count' => $categories->count(),
            'locations_count' => $locations->count()
        ]);

        return view('rental-requests.index', compact(
            'rentalRequests', 'recommendedRequests', 'user', 'categories', 'locations'
        ));
    }

    /**
     * Получить заявки для арендодателя
     */
    private function getLessorRequests(Request $request)
    {
        // Используем существующий сервис для получения заявок арендодателя
        return $this->rentalRequestService->getActiveRequestsForLessor(
            Auth::user(),
            $request->all()
        );
    }

    /**
     * Получить публичные заявки (для гостей и арендаторов)
     */
    private function getPublicRequests(Request $request)
    {
        // Логика из PublicRentalRequestController
        $query = RentalRequest::where('status', 'active')
            ->where('visibility', 'public')
            ->where('expires_at', '>', now())
            ->with(['items.category', 'location'])
            ->withCount(['responses as active_proposals_count' => function ($q) {
                $q->where('status', 'pending')->where('expires_at', '>', now());
            }]);

        // Фильтрация по категории
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('items', function ($q) use ($request) {
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
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($request->get('per_page', 15));
    }

    /**
     * Получить рекомендованные заявки
     */
    private function getRecommendedRequests($user)
    {
        // Используем существующий сервис для получения рекомендованных заявок
        return $this->matchingService->getRecommendedRequests($user);
    }
}
