<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Middleware\CheckCompanyVerified; // ПРАВИЛЬНЫЙ ИМПОРТ
use App\Http\Middleware\CheckUserType;

class RentalRequestController extends Controller
{
    public function __construct(private RentalRequestService $rentalRequestService)
    {
        $this->middleware('auth');
        $this->middleware(CheckCompanyVerified::class); // ПРАВИЛЬНОЕ ИСПОЛЬЗОВАНИЕ
        $this->middleware(CheckUserType::class . ':lessee');
    }

    /**
     * Список заявок арендатора
     */
   public function index(Request $request)
    {
        $status = $request->get('status', 'active');
        $perPage = $request->get('per_page', 15);

        $requests = RentalRequest::with(['category', 'location', 'responses.equipment', 'responses.lessor.company'])
            ->where('user_id', auth()->id())
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // ВЫЧИСЛЯЕМ СТАТИСТИКУ И ПЕРЕДАЕМ В ШАБЛОН
        $stats = $this->getRequestStats(auth()->id());

        if ($request->wantsJson()) {
            return response()->json([
                'requests' => $requests,
                'stats' => $stats
            ]);
        }

        return view('lessee.rental_requests.index', compact('requests', 'status', 'stats'));
    }

    /**
     * Форма создания новой заявки
     */
    public function create()
    {
        // Теперь scope withEquipment будет работать правильно
        $categories = Category::root()
            ->withEquipment() // ← использует исправленный scope
            ->with('children')
            ->get();

        $locations = Location::all();

        return view('lessee.rental_requests.create', compact('categories', 'locations'));
    }

    /**
     * Сохранение новой заявки
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'category_id' => 'required|exists:equipment_categories,id',
            'rental_period_start' => 'required|date|after:today',
            'rental_period_end' => 'required|date|after:rental_period_start',
            'budget_from' => 'required|numeric|min:0',
            'budget_to' => 'required|numeric|min:budget_from',
            'location_id' => 'required|exists:locations,id',
            'delivery_required' => 'boolean',
            'specifications' => 'nullable|array'
        ]);

        try {
            $rentalRequest = $this->rentalRequestService->createRentalRequest(
                $validated,
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно создана',
                'request' => $rentalRequest,
                'redirect_url' => route('lessee.rental-requests.show', $rentalRequest->id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заявки: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Просмотр конкретной заявки
     */
    public function show($id)
    {
        $rentalRequest = RentalRequest::with([
            'category',
            'location',
            'responses' => function ($query) {
                $query->with(['lessor.company', 'equipment.images', 'equipment.rentalTerms'])
                      ->orderBy('created_at', 'desc');
            }
        ])->where('user_id', auth()->id())->findOrFail($id);

        // Увеличиваем счетчик просмотров
        if (auth()->id() === $rentalRequest->user_id) {
            $rentalRequest->increment('views_count');
        }

        return view('lessee.rental_requests.show', compact('rentalRequest'));
    }

    /**
     * Принятие предложения по заявке
     */
    public function acceptProposal(Request $request, $requestId, $proposalId): JsonResponse
    {
        try {
            $proposal = RentalRequestResponse::where('rental_request_id', $requestId)
                ->where('id', $proposalId)
                ->firstOrFail();

            $proposalService = app(\App\Services\ProposalManagementService::class);
            $proposalService->acceptProposal($proposal);

            return response()->json([
                'success' => true,
                'message' => 'Предложение принято. Заказ создан.',
                'order_id' => $proposal->fresh()->order_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Статистика по заявкам
     */
     private function getRequestStats($userId): array
    {
        return [
            'total' => RentalRequest::where('user_id', $userId)->count(),
            'active' => RentalRequest::where('user_id', $userId)->where('status', 'active')->count(),
            'processing' => RentalRequest::where('user_id', $userId)->where('status', 'processing')->count(),
            'completed' => RentalRequest::where('user_id', $userId)->where('status', 'completed')->count(),
            'cancelled' => RentalRequest::where('user_id', $userId)->where('status', 'cancelled')->count(),
        ];
    }
}
