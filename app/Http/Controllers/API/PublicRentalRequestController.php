<?php
// app/Http/Controllers/API/PublicRentalRequestController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestPricingService;
use App\Services\EquipmentSpecificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicRentalRequestController extends Controller
{

      protected $specificationService;

    // 🔥 ДОБАВЛЕНО: Внедрение сервиса спецификаций
    public function __construct(EquipmentSpecificationService $specificationService)
    {
        $this->specificationService = $specificationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            \Log::info('PublicRentalRequestController index called', $request->all());

            $query = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->with(['items.category', 'location', 'user.company']) // Добавили user.company
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

            // 🔥 ДОБАВЛЕНО: Преобразование цен для арендодателей
            $user = auth()->user();
            $isLessor = $user && $user->company && $user->company->is_lessor;

            if ($isLessor) {
                $pricingService = app(\App\Services\RentalRequestPricingService::class);

                $requests->getCollection()->transform(function ($rentalRequest) use ($pricingService) {
                    try {
                        $lessorPricing = $pricingService->calculateLessorPrices($rentalRequest);
                        $rentalRequest->lessor_pricing = $lessorPricing;

                        // Скрываем оригинальные цены от арендодателя
                        unset(
                            $rentalRequest->hourly_rate,
                            $rentalRequest->max_hourly_rate,
                            $rentalRequest->total_budget
                        );

                    } catch (\Exception $e) {
                        \Log::error('Error transforming request prices: ' . $e->getMessage(), [
                            'request_id' => $rentalRequest->id
                        ]);
                    }

                    return $rentalRequest;
                });
            }

            $filterCategories = Category::where('is_active', true)->get();
            $locations = Location::all();

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
            \Log::info('PublicRentalRequestController show called', ['id' => $id]);

            $request = RentalRequest::where('status', 'active')
                ->where('visibility', 'public')
                ->where('expires_at', '>', now())
                ->with([
                    'items.category',
                    'location',
                    'user.company'
                ])
                ->findOrFail($id);

            \Log::info('Request found', ['request_id' => $request->id]);

            // 🔥 КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ: Используем аксессор модели
            $request->items->each(function ($item) {
                // Просто используем аксессор formatted_specifications из модели
                // который УЖЕ правильно обрабатывает структуру с labels/values
                $item->formatted_specs = $item->formatted_specifications;

                // Гарантируем, что категория будет строкой
                $item->category_name = $item->category->name ?? 'Без категории';

                \Log::debug('Item processed', [
                    'item_id' => $item->id,
                    'has_specs' => !empty($item->specifications),
                    'formatted_specs_count' => count($item->formatted_specs),
                    'specifications_structure' => $item->specifications
                ]);
            });

            // Группируем items по категориям для фронтенда
            $request->grouped_items = $request->items->groupBy('category_name')->map(function ($items, $categoryName) {
                return [
                    'category_name' => $categoryName,
                    'items' => $items,
                    'items_count' => $items->count(),
                    'total_quantity' => $items->sum('quantity')
                ];
            })->values();

            $user = auth()->user();
            $isLessor = $user && $user->company && $user->company->is_lessor;

            if ($isLessor) {
                try {
                    $pricingService = app(RentalRequestPricingService::class);
                    $lessorPricing = $pricingService->calculateLessorPrices($request);
                    $request->lessor_pricing = $lessorPricing;
                } catch (\Exception $e) {
                    \Log::error('Pricing service error: ' . $e->getMessage());
                }
            }

            $request->increment('views_count');

            return response()->json([
                'success' => true,
                'data' => $request
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in PublicRentalRequestController show: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки заявки',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function formatItemSpecifications($item): array
    {
        if (empty($item->specifications)) {
            return [];
        }

        try {
            $formatted = [];
            $specsArray = is_array($item->specifications) ? $item->specifications : [];
            $categoryName = $item->category->name ?? '';

            // Обрабатываем оба формата данных
            if (isset($specsArray['values']) && is_array($specsArray['values'])) {
                // Новый формат с metadata (values/labels)
                foreach ($specsArray['values'] as $key => $value) {
                    if ($value !== null && $value !== '' && $value !== 'null') {
                        $formatted[] = $this->specificationService->formatSpecification($key, $value, $categoryName);
                    }
                }
            } else {
                // Старый формат - простой ассоциативный массив
                foreach ($specsArray as $key => $value) {
                    if ($key !== 'labels' && $value !== null && $value !== '' && $value !== 'null') {
                        $formatted[] = $this->specificationService->formatSpecification($key, $value, $categoryName);
                    }
                }
            }

            return $formatted;

        } catch (\Exception $e) {
            \Log::error('Error formatting item specifications: ' . $e->getMessage(), [
                'item_id' => $item->id,
                'category' => $item->category->name ?? 'unknown'
            ]);
            return [];
        }
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
