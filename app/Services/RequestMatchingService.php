<?php
// app/Services/RequestMatchingService.php
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
            'budget_from' => $request->budget_from,
            'budget_to' => $request->budget_to,
            'types' => [
                'budget_from_type' => gettype($request->budget_from),
                'budget_to_type' => gettype($request->budget_to)
            ]
        ]);

        $relevantLessors = $this->findRelevantLessors($request);

        \Log::info("Found relevant lessors:", ['count' => $relevantLessors->count()]);

        foreach ($relevantLessors as $lessor) {
            try {
                $lessor->notify(new NewRentalRequestNotification($request));
                \Log::info("Notification sent to lessor:", ['lessor_id' => $lessor->id]);
            } catch (\Exception $e) {
                \Log::error("Error sending notification to lessor {$lessor->id}: " . $e->getMessage());
            }
        }
    }

    private function findRelevantLessors(RentalRequest $request)
    {
        return User::whereHas('company', function ($query) use ($request) {
                $query->where('type', 'lessor')
                    ->where('is_verified', true);
            })
            ->whereHas('equipment', function ($query) use ($request) {
                $query->where('category_id', $request->category_id)
                    ->where('is_approved', true); // ← ЗАМЕНИТЬ и здесь
            })
            ->whereHas('locations', function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            })
            ->get();
    }

    public function calculateMatchScore(RentalRequest $request, $equipment): float
    {
        $score = 0;

        // Совпадение по категории (базовый балл)
        if ($equipment->category_id === $request->category_id) {
            $score += 30;
        }

        // Совпадение по бюджету
        if ($equipment->rental_price >= $request->budget_from &&
            $equipment->rental_price <= $request->budget_to) {
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
        // Получаем ID категорий оборудования арендодателя
        $lessorCategoryIds = \App\Models\Equipment::where('company_id', $lessor->company_id)
            ->where('is_approved', true) // ← ЗАМЕНИТЬ is_active на is_approved
            ->pluck('category_id')
            ->unique()
            ->toArray();

        // Если у арендодателя нет оборудования, возвращаем 0
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
        // Получаем ID категорий оборудования арендодателя
        $lessorCategoryIds = $lessor->company->equipment()
            ->where('is_approved', true) // ← ЗАМЕНИТЬ is_active на is_approved
            ->pluck('category_id')
            ->unique()
            ->toArray();

        if (empty($lessorCategoryIds)) {
            return collect();
        }

        // Получаем рекомендуемые заявки (активные, подходящие по категориям)
        $recommendedRequests = RentalRequest::active()
            ->whereIn('category_id', $lessorCategoryIds)
            ->where('created_at', '>', now()->subDays(30))
            ->with(['category', 'location', 'user.company'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Добавляем оценку соответствия для каждой заявки
        return $recommendedRequests->map(function ($request) use ($lessor) {
            $request->match_score = $this->calculateMatchScore($request, $lessor);
            return $request;
        })->sortByDesc('match_score');
    }
}
