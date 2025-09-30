<?php
// app/Http/Controllers/API/PublicRentalRequestController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicRentalRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            \Log::info('PublicRentalRequestController index called', $request->all());

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

            $requests = $query->paginate($request->get('per_page', 15));

            // ИСПРАВЛЕНО: для категорий используем is_active, для локаций - все
            $filterCategories = Category::where('is_active', true)->get();
            $locations = Location::all(); // Все локации, так как нет поля is_active

            return response()->json([
                'success' => true,
                'data' => $requests,
                'filters' => [
                    'categories' => $filterCategories,
                    'locations' => $locations
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in PublicRentalRequestController index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке заявок',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $request = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->with([
                    'items.category', // Убедимся что категории загружаются
                    'location',
                    'user.company'
                ])
                ->findOrFail($id);

            // Детальная отладка
            \Log::debug('Public rental request items data', [
                'request_id' => $request->id,
                'items_count' => $request->items->count(),
                'items_with_category' => $request->items->where('category_id', '!=', null)->count(),
                'categories_loaded' => $request->items->pluck('category')->filter()->count()
            ]);

            // Увеличиваем счетчик просмотров
            $request->increment('views_count');

            return response()->json([
                'success' => true,
                'data' => $this->transformForPublic($request, auth()->check())
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in PublicRentalRequestController show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Заявка не найдена или недоступна'
            ], 404);
        }
    }

    private function transformForPublic(RentalRequest $request, bool $isAuthenticated = false): array
    {
        $data = [
            'id' => $request->id,
            'title' => $request->title,
            'description' => $request->description,
            'rental_period' => [
                'start' => $request->rental_period_start,
                'end' => $request->rental_period_end,
                'days' => $request->rental_period_start->diffInDays($request->rental_period_end) + 1
            ],
            'location' => $request->location,
            'items' => $request->items->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'quantity' => $item->quantity,
                    'specifications' => $item->formatted_specifications
                ];
            }),
            'active_proposals_count' => $request->active_proposals_count ?? 0,
            'views_count' => $request->views_count,
            'created_at' => $request->created_at,
        ];

        // Для авторизованных арендодателей показываем больше данных
        if ($isAuthenticated && auth()->check() && auth()->user()->is_lessor) {
            $data['hourly_rate'] = $request->hourly_rate;
            $data['max_hourly_rate'] = $request->max_hourly_rate;
            $data['rental_conditions'] = $request->rental_conditions;
            $data['total_budget'] = $request->total_budget;
        }

        return $data;
    }

    // Временная заглушка для createProposal - реализуем позже
    public function createProposal(Request $request, $rentalRequestId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Функционал предложений временно недоступен'
        ], 501);
    }
}
