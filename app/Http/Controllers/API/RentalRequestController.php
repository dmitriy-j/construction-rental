<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RentalRequestController extends Controller
{
    public function __construct(private RentalRequestService $rentalRequestService)
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->get('status', 'all');
            $search = $request->get('search', '');
            $perPage = $request->get('per_page', 15);
            $sort = $request->get('sort', 'newest');

            $query = RentalRequest::with([
                'items.category',
                'location',
                'responses.lessor.company',
                'responses.equipment'
            ])
            ->withCount(['responses', 'items'])
            ->where('user_id', auth()->id());

            // Фильтрация по статусу
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            // Поиск
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Сортировка
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'proposals':
                    $query->orderBy('responses_count', 'desc');
                    break;
                case 'budget':
                    $query->orderBy('total_budget', 'desc'); // ИСПРАВЛЕНО: используем total_budget
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }

            $requests = $query->paginate($perPage);

            // Преобразуем данные для фронтенда - используем total_budget
            $requests->getCollection()->transform(function ($request) {
                $request->display_budget = $request->total_budget ?? 0;
                return $request;
            });

            return response()->json([
                'success' => true,
                'data' => $requests,
                'filters' => [
                    'status' => $status,
                    'search' => $search,
                    'sort' => $sort,
                    'per_page' => $perPage
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in rental requests index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке заявок: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            \Log::info('API RentalRequest show called', ['id' => $id, 'user_id' => auth()->id()]);

            $request = RentalRequest::with([
                'items.category',
                'location',
                'responses.lessor.company',
                'responses.equipment.images'
            ])->where('user_id', auth()->id())->findOrFail($id);

            \Log::info('Request found', ['request_id' => $request->id, 'items_count' => $request->items->count()]);

            // Безопасная обработка items
            $request->items->each(function ($item) {
                try {
                    // Форматируем спецификации
                    if (!empty($item->specifications)) {
                        $item->formatted_specifications = $item->formatted_specifications;
                    } else {
                        $item->formatted_specifications = [];
                    }

                    // Добавляем тип условий
                    $item->conditions_type = $item->conditions_type;
                    $item->display_conditions = $item->display_conditions;

                    \Log::debug('Item processed', [
                        'item_id' => $item->id,
                        'specs_count' => count($item->formatted_specifications),
                        'conditions_type' => $item->conditions_type
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error processing item ' . $item->id, ['error' => $e->getMessage()]);
                    $item->formatted_specifications = [];
                    $item->conditions_type = 'general';
                    $item->display_conditions = [];
                }
            });

            // Безопасная группировка по категориям
            $groupedByCategory = [];
            try {
                $groupedByCategory = $request->items->groupBy('category_id')->map(function ($items, $categoryId) {
                    $category = Category::find($categoryId);
                    return [
                        'category_id' => $categoryId,
                        'category_name' => $category->name ?? 'Без категории',
                        'items_count' => $items->count(),
                        'total_quantity' => $items->sum('quantity'),
                        'items' => $items
                    ];
                })->values()->toArray();
            } catch (\Exception $e) {
                \Log::error('Error grouping by category', ['error' => $e->getMessage()]);
                $groupedByCategory = [];
            }

            $responseData = [
                'success' => true,
                'data' => $request,
                'grouped_by_category' => $groupedByCategory,
                'summary' => [
                    'total_items' => $request->items->count(),
                    'total_quantity' => $request->items->sum('quantity'),
                    'categories_count' => $request->items->groupBy('category_id')->count()
                ]
            ];

            \Log::info('API response prepared', ['request_id' => $request->id]);

            return response()->json($responseData);

        } catch (\Exception $e) {
            \Log::error('API Error in rental request show', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Заявка не найдена или произошла ошибка сервера'
            ], 500);
        }
    }

    public function pause($id): JsonResponse
    {
        try {
            Log::info('Pausing rental request', ['request_id' => $id, 'user_id' => auth()->id()]);

            $rentalRequest = RentalRequest::where('user_id', auth()->id())->findOrFail($id);

            // ОБНОВЛЯЕМ: используем статус 'paused' если он доступен
            if ($rentalRequest->status === 'active') {
                // Проверяем, поддерживает ли база данных статус 'paused'
                $rentalRequest->update(['status' => 'paused']);

                Log::info('Rental request paused', ['request_id' => $id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Заявка успешно приостановлена',
                    'data' => [
                        'id' => $rentalRequest->id,
                        'status' => $rentalRequest->status,
                        'status_text' => 'Приостановлена'
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Невозможно приостановить заявку с текущим статусом: ' . $rentalRequest->status
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Error pausing rental request: ' . $e->getMessage(), [
                'request_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при приостановке заявки'
            ], 500);
        }
    }

    private function canPauseRequest(RentalRequest $rentalRequest): bool
    {
        $allowedStatuses = [
            RentalRequest::STATUS_ACTIVE,
            RentalRequest::STATUS_PROCESSING
        ];

        return in_array($rentalRequest->status, $allowedStatuses);
    }

   public function resume($id): JsonResponse
    {
        try {
            Log::info('Resuming rental request', ['request_id' => $id, 'user_id' => auth()->id()]);

            $rentalRequest = RentalRequest::where('user_id', auth()->id())->findOrFail($id);

            // ДОБАВЛЯЕМ детальное логирование
            Log::info('Current request status before resume', [
                'request_id' => $id,
                'current_status' => $rentalRequest->status,
                'allowed_statuses' => ['paused', 'processing']
            ]);

            // Возобновляем из статусов 'paused' и 'processing'
            if (in_array($rentalRequest->status, ['paused', 'processing'])) {
                $rentalRequest->update(['status' => 'active']);

                Log::info('Rental request resumed successfully', ['request_id' => $id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Заявка успешно возобновлена',
                    'data' => [
                        'id' => $rentalRequest->id,
                        'status' => $rentalRequest->status,
                        'status_text' => 'Активна'
                    ]
                ]);
            } else {
                Log::warning('Cannot resume request - invalid status', [
                    'request_id' => $id,
                    'current_status' => $rentalRequest->status
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Заявка не находится в приостановленном состоянии. Текущий статус: ' . $rentalRequest->status
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Error resuming rental request: ' . $e->getMessage(), [
                'request_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при возобновлении заявки: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rentalRequest = RentalRequest::where('user_id', auth()->id())
                ->findOrFail($id);

            // Валидация данных
             $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'hourly_rate' => 'required|numeric|min:0',
                'rental_period_start' => 'required|date',
                'rental_period_end' => 'required|date|after_or_equal:rental_period_start',
                'location_id' => 'required|exists:locations,id',
                'items' => 'required|array|min:1',
                'items.*.category_id' => 'required|exists:equipment_categories,id', // ← ИСПРАВЛЕНО
                'items.*.quantity' => 'required|integer|min:1'
            ]);

            // Обновление заявки через сервис
            $rentalRequestService = app(\App\Services\RentalRequestService::class);
            $updatedRequest = $rentalRequestService->updateRentalRequest($rentalRequest, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно обновлена',
                'data' => $updatedRequest
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating rental request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении заявки: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id): JsonResponse
    {
        try {
            $rentalRequest = RentalRequest::where('user_id', auth()->id())->findOrFail($id);
            $rentalRequest->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Заявка отменена'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене заявки'
            ], 500);
        }
    }

    public function acceptProposal($requestId, $proposalId): JsonResponse
    {
        try {
            $proposal = RentalRequestResponse::where('rental_request_id', $requestId)
                ->where('id', $proposalId)
                ->firstOrFail();

            $proposal->update(['status' => 'accepted']);

            return response()->json([
                'success' => true,
                'message' => 'Предложение принято'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при принятии предложения'
            ], 500);
        }
    }

    public function rejectProposal($requestId, $proposalId): JsonResponse
    {
        try {
            $proposal = RentalRequestResponse::where('rental_request_id', $requestId)
                ->where('id', $proposalId)
                ->firstOrFail();

            $proposal->update(['status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => 'Предложение отклонено'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отклонении предложения'
            ], 500);
        }
    }

    public function showForVue($id)
    {
        try {
            $request = RentalRequest::with([
                'items.category',
                'location',
                'responses.lessor.company',
                'responses.equipment.images'
            ])->where('user_id', auth()->id())->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $request
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Заявка не найдена'
            ], 404);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $userId = auth()->id();

            $stats = [
                'total' => RentalRequest::where('user_id', $userId)->count(),
                'active' => RentalRequest::where('user_id', $userId)->where('status', 'active')->count(),
                'processing' => RentalRequest::where('user_id', $userId)->where('status', 'processing')->count(),
                'completed' => RentalRequest::where('user_id', $userId)->where('status', 'completed')->count(),
                'cancelled' => RentalRequest::where('user_id', $userId)->where('status', 'cancelled')->count(),
            ];

            // Общее количество позиций во всех заявках
            $totalItemsCount = RentalRequest::where('user_id', $userId)
                ->with('items')
                ->get()
                ->sum(function($request) {
                    return $request->items->count();
                });

            // Общее количество предложений
            $totalProposalsCount = RentalRequest::where('user_id', $userId)->sum('responses_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'requests' => $stats,
                    'total_items' => $totalItemsCount,
                    'total_proposals' => $totalProposalsCount
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Error in rental requests stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке статистики'
            ], 500);
        }
    }

    // Добавим метод для тестирования
    public function test()
    {
        return response()->json([
            'message' => 'API тест успешен',
            'user' => auth()->user()->email,
            'requests_count' => RentalRequest::where('user_id', auth()->id())->count()
        ]);
    }
}
