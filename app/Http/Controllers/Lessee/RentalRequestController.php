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
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreRentalRequestRequest;

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

    private function normalizeNumber($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Если уже число - возвращаем как есть
        if (is_numeric($value)) {
            return $value;
        }

        // Преобразуем в строку
        $value = (string) $value;

        \Log::info('Normalizing value:', ['raw' => $value]);

        // Убираем все пробелы (разделители тысяч)
        $value = str_replace(' ', '', $value);

        // Заменяем русскую запятую на точку
        $value = str_replace(',', '.', $value);

        // Убираем все нечисловые символы кроме точки, минуса и цифр
        $value = preg_replace('/[^\d\.\-]/', '', $value);

        // Если после обработки пустая строка
        if ($value === '' || $value === '-') {
            return null;
        }

        // Проверяем, является ли результат числом
        if (!is_numeric($value)) {
            \Log::warning('Value is not numeric after normalization:', ['value' => $value]);
            return null;
        }

        // Для целых чисел возвращаем int, для дробных - float
        $result = strpos($value, '.') !== false ? (float) $value : (int) $value;

        \Log::info('Normalization result:', ['input' => $value, 'output' => $result]);

        return $result;
    }

    public function store(StoreRentalRequestRequest $request): JsonResponse
    {
        // Временное решение: обход brick/math проблемы
        ini_set('precision', 14);
        ini_set('serialize_precision', 14);

        \Log::info("=== BRICK/MATH DEBUG ===");
        \Log::info("Raw budget_from:", ['value' => $request->budget_from, 'type' => gettype($request->budget_from)]);
        \Log::info("Raw budget_to:", ['value' => $request->budget_to, 'type' => gettype($request->budget_to)]);

        try {
            $validated = $request->validated();

            // Явно преобразуем в примитивные типы PHP
            $validated['budget_from'] = floatval($validated['budget_from']);
            $validated['budget_to'] = floatval($validated['budget_to']);

            \Log::info("After float conversion:", [
                'budget_from' => $validated['budget_from'],
                'budget_from_type' => gettype($validated['budget_from']),
                'budget_to' => $validated['budget_to'],
                'budget_to_type' => gettype($validated['budget_to'])
            ]);

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

        } catch (\Brick\Math\Exception\NumberFormatException $e) {
            \Log::error("Brick/Math Exception:", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Альтернативный способ создания заявки
            return $this->createRentalRequestAlternative($request);

        } catch (\Exception $e) {
            \Log::error("General exception:", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
