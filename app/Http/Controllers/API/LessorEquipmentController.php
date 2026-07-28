<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\RentalRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LessorEquipmentController extends Controller
{
    /**
     * Получить технику арендодателя, сгруппированную по категориям
     * GET /api/lessor/equipment/categories?ids[]=1&ids[]=3
     */
    public function getByCategories(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:equipment_categories,id'
            ]);

            $user = auth()->user();
            $categoryIds = $validated['ids'];

            // Получаем категории, которые запрашиваются в заявке
            $categories = Category::whereIn('id', $categoryIds)
                ->where('is_active', true)
                ->get();

            $result = [];

            foreach ($categories as $category) {
                $equipment = Equipment::where('company_id', $user->company_id)
                    ->where('category_id', $category->id)
                    ->where('is_approved', true)
                    ->with(['specifications', 'category', 'rentalTerms'])
                    ->get();

                if ($equipment->isEmpty()) {
                    continue;
                }

                $equipmentData = $equipment->map(function ($item) {
                    $term = $item->rentalTerms->first();

                    // Безопасно получаем спецификации
                    $specifications = [];
                    if ($item->specifications) {
                        $specifications = $item->specifications->map(function ($spec) {
                            return [
                                'key' => $spec->key,
                                'name' => $spec->name,
                                'value' => $spec->value,
                                'unit' => $spec->unit,
                            ];
                        })->values()->toArray();
                    }

                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'brand' => $item->brand,
                        'model' => $item->model,
                        'year' => $item->year,
                        'license_plate' => $item->license_plate,
                        'category_id' => $item->category_id,
                        'category_name' => $item->category->name ?? '',
                        'price_per_hour' => $term ? (float) $term->price_per_hour : 0,
                        'min_rental_period' => $term ? (int) ($term->min_rental_hours ?? 1) : 1,
                        'specifications' => $specifications,
                        'has_active_terms' => $item->hasActiveRentalTerms(),
                        'availability_status' => $item->availability_status,
                    ];
                })->values()->toArray();

                $result[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'equipment_count' => count($equipmentData),
                    'equipment' => $equipmentData,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in LessorEquipmentController@getByCategories: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки техники по категориям'
            ], 500);
        }
    }

    public function getAvailableForRequest(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'request_id' => 'required|exists:rental_requests,id',
                'category_ids' => 'required|array',
                'category_ids.*' => 'exists:equipment_categories,id'
            ]);

            $rentalRequest = RentalRequest::findOrFail($validated['request_id']);

            // Получаем технику арендодателя в нужных категориях
            $equipment = Equipment::where('company_id', $user->company_id)
                ->where('is_approved', true)
                ->whereIn('category_id', $validated['category_ids'])
                ->with(['specifications', 'category'])
                ->get()
                ->map(function ($item) use ($rentalRequest) {
                    // Проверяем доступность на период заявки
                    $item->availability_status = $item->isAvailableForPeriod(
                        $rentalRequest->rental_period_start,
                        $rentalRequest->rental_period_end
                    ) ? 'available' : 'unavailable';

                    return $item;
                });

            return response()->json([
                'success' => true,
                'data' => $equipment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading available equipment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки техники'
            ], 500);
        }
    }
}
