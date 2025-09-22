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
        $relevantLessors = $this->findRelevantLessors($request);

        foreach ($relevantLessors as $lessor) {
            $lessor->notify(new NewRentalRequestNotification($request));
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
                    ->where('is_active', true);
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
        return \App\Models\RentalRequest::active()
            ->where('category_id', function($query) use ($lessor) {
                $query->select('category_id')
                    ->from('equipment')
                    ->where('company_id', $lessor->company_id)
                    ->where('is_active', true);
            })
            ->where('created_at', '>', now()->subDays(7))
            ->count();
    }
}
