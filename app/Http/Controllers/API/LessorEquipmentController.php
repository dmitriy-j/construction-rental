<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\RentalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LessorEquipmentController extends Controller
{
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
