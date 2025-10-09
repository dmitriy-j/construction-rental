<?php

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\NewRentalRequestNotification;

class RequestMatchingService
{
    public function notifyRelevantLessors(RentalRequest $request): void
    {
        \Log::info("Starting lessor notification for request:", [
            'request_id' => $request->id,
            'equipment_quantity' => $request->equipment_quantity,
            'calculated_budget_from' => $request->calculated_budget_from,
            'calculated_budget_to' => $request->calculated_budget_to
        ]);

        $relevantLessors = $this->findRelevantLessors($request);

        \Log::info("Found relevant lessors:", ['count' => $relevantLessors->count()]);

        foreach ($relevantLessors as $lessor) {
            try {
                // Проверяем, может ли арендодатель удовлетворить потребность в количестве
                if ($this->canLessorSatisfyQuantity($lessor, $request)) {
                    $lessor->notify(new NewRentalRequestNotification($request));
                    \Log::info("Notification sent to lessor:", ['lessor_id' => $lessor->id]);
                } else {
                    \Log::info("Lessor cannot satisfy quantity requirement:", [
                        'lessor_id' => $lessor->id,
                        'required_quantity' => $request->equipment_quantity
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error("Error sending notification to lessor {$lessor->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * ПРОВЕРКА, МОЖЕТ ЛИ АРЕНДОДАТЕЛЬ УДОВЛЕТВОРИТЬ ПОТРЕБНОСТЬ В КОЛИЧЕСТВЕ
     */
    private function canLessorSatisfyQuantity(User $lessor, RentalRequest $request): bool
    {
        $availableEquipmentCount = $lessor->company->equipment()
            ->where('category_id', $request->category_id)
            ->where('is_approved', true)
            ->count();

        return $availableEquipmentCount >= $request->equipment_quantity;
    }

    public function findRelevantLessors(RentalRequest $request)
    {
        return User::whereHas('company', function ($query) use ($request) {
                $query->where('is_lessor', true)->where('status', 'verified')
                    ->whereHas('equipment', function ($q) use ($request) {
                        $q->where('category_id', $request->category_id)
                            ->where('is_approved', true)
                            ->where(function ($q2) use ($request) {
                                if ($request->desired_specifications) {
                                    $this->applySpecificationFilters($q2, $request->desired_specifications);
                                }
                            });
                    });
            })
            ->whereHas('locations', function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            })
            ->get();
    }

    /**
     * РАСЧЕТ СОВПАДЕНИЯ (ОБНОВЛЕННАЯ ВЕРСИЯ)
     */
    public function calculateMatchScore(RentalRequest $request, $equipment): float
    {
        $score = 0;

        // Совпадение по категории (базовый балл)
        if ($equipment->category_id === $request->category_id) {
            $score += 30;
        }

        // Совпадение по РАССЧИТАННОМУ бюджету
        $equipmentPrice = $equipment->rental_price ?? 0;
        $budgetFrom = $request->calculated_budget_from ?? $request->budget_from;
        $budgetTo = $request->calculated_budget_to ?? $request->budget_to;

        if ($equipmentPrice >= $budgetFrom && $equipmentPrice <= $budgetTo) {
            $score += 40;
        }

        // Наличие в нужной локации
        if ($equipment->locations->contains($request->location_id)) {
            $score += 30;
        }

        return min($score, 100);
    }

    public static function getNewRequestsCount(User $lessor): int
    {
        $lessorCategoryIds = \App\Models\Equipment::where('company_id', $lessor->company_id)
            ->where('is_approved', true)
            ->pluck('category_id')
            ->unique()
            ->toArray();

        if (empty($lessorCategoryIds)) {
            return 0;
        }

        return \App\Models\RentalRequest::active()
            ->whereIn('category_id', $lessorCategoryIds)
            ->where('created_at', '>', now()->subDays(7))
            ->count();
    }

    public function getRecommendedRequests(User $lessor)
    {
        $lessorCategoryIds = $lessor->company->equipment()
            ->where('is_approved', true)
            ->pluck('category_id')
            ->unique()
            ->toArray();

        if (empty($lessorCategoryIds)) {
            return collect();
        }

        // Исправленный запрос - фильтрация через связь с items
        $recommendedRequests = RentalRequest::active()
            ->whereHas('items', function ($query) use ($lessorCategoryIds) {
                $query->whereIn('category_id', $lessorCategoryIds);
            })
            ->where('created_at', '>', now()->subDays(30))
            ->with(['items.category', 'location', 'user.company']) // Добавляем загрузку items с категориями
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $recommendedRequests->map(function ($request) use ($lessor) {
            $request->match_score = $this->calculateMatchScore($request, $lessor);
            return $request;
        })->sortByDesc('match_score');
    }
}
