<?php
// app/Services/LessorAnalyticsService.php

namespace App\Services;

use App\Models\User;
use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LessorAnalyticsService
{
    public function getProposalAnalytics(User $user, array $filters = [])
    {
        $lessorId = $user->id;
        $companyId = $user->company_id;

        // Базовые метрики
        $baseQuery = RentalRequestResponse::where('lessor_id', $lessorId);

        // Применяем фильтры по дате
        if (!empty($filters['date_range'])) {
            $dateRange = $this->parseDateRange($filters['date_range']);
            $baseQuery->whereBetween('created_at', $dateRange);
        }

        $totalProposals = $baseQuery->count();
        $acceptedProposals = (clone $baseQuery)->where('status', 'accepted')->count();
        $pendingProposals = (clone $baseQuery)->where('status', 'pending')->count();
        $rejectedProposals = (clone $baseQuery)->where('status', 'rejected')->count();

        // Расчет конверсии
        $conversionRate = $totalProposals > 0 ? round(($acceptedProposals / $totalProposals) * 100, 2) : 0;

        // Доходы
        $revenueData = $this->calculateRevenue($lessorId, $filters);

        // Активность по дням
        $dailyActivity = $this->getDailyActivity($lessorId, $filters);

        // Сравнение с рыночными ценами
        $priceComparison = $this->getPriceComparison($companyId, $filters);

        return [
            'total_proposals' => $totalProposals,
            'accepted_proposals' => $acceptedProposals,
            'pending_proposals' => $pendingProposals,
            'rejected_proposals' => $rejectedProposals,
            'conversion_rate' => $conversionRate,
            'revenue' => $revenueData,
            'daily_activity' => $dailyActivity,
            'price_comparison' => $priceComparison,
            'performance_metrics' => $this->calculatePerformanceMetrics($lessorId, $filters)
        ];
    }

    public function getRequestProposalAnalytics($requestId, $lessorId)
    {
        return DB::table('rental_request_responses')
            ->select(
                DB::raw('COUNT(*) as total_proposals'),
                DB::raw('SUM(CASE WHEN lessor_id = ? THEN 1 ELSE 0 END) as my_proposals'),
                DB::raw('SUM(CASE WHEN lessor_id = ? AND status = "accepted" THEN 1 ELSE 0 END) as my_accepted_proposals'),
                DB::raw('AVG(proposed_price) as avg_price'),
                DB::raw('MIN(proposed_price) as min_price'),
                DB::raw('MAX(proposed_price) as max_price')
            )
            ->addBinding($lessorId, 'select')
            ->addBinding($lessorId, 'select')
            ->where('rental_request_id', $requestId)
            ->first();
    }

    private function calculateRevenue($lessorId, $filters)
    {
        return DB::table('rental_request_responses')
            ->where('lessor_id', $lessorId)
            ->where('status', 'accepted')
            ->when(!empty($filters['date_range']), function ($query) use ($filters) {
                $dateRange = $this->parseDateRange($filters['date_range']);
                return $query->whereBetween('created_at', $dateRange);
            })
            ->select(
                DB::raw('SUM(proposed_price) as total_revenue'),
                DB::raw('AVG(proposed_price) as avg_order_value'),
                DB::raw('COUNT(DISTINCT rental_request_id) as completed_requests')
            )
            ->first();
    }

    private function getDailyActivity($lessorId, $filters)
    {
        return DB::table('rental_request_responses')
            ->where('lessor_id', $lessorId)
            ->when(!empty($filters['date_range']), function ($query) use ($filters) {
                $dateRange = $this->parseDateRange($filters['date_range']);
                return $query->whereBetween('created_at', $dateRange);
            })
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as proposals_count'),
                DB::raw('SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
    }

    private function getPriceComparison($companyId, $filters)
    {
        // Сравнение цен компании со средними рыночными
        return DB::table('rental_request_responses as rrr')
            ->join('equipment as e', 'rrr.equipment_id', '=', 'e.id')
            ->where('e.company_id', $companyId)
            ->when(!empty($filters['date_range']), function ($query) use ($filters) {
                $dateRange = $this->parseDateRange($filters['date_range']);
                return $query->whereBetween('rrr.created_at', $dateRange);
            })
            ->select(
                DB::raw('AVG(rrr.proposed_price) as company_avg_price'),
                DB::raw('(SELECT AVG(proposed_price) FROM rental_request_responses
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as market_avg_price')
            )
            ->first();
    }

    private function calculatePerformanceMetrics($lessorId, $filters)
    {
        return [
            'response_time_avg' => $this->calculateAverageResponseTime($lessorId, $filters),
            'acceptance_rate' => $this->calculateAcceptanceRate($lessorId, $filters),
            'customer_rating' => $this->calculateCustomerRating($lessorId, $filters)
        ];
    }

    private function parseDateRange($dateRange)
    {
        // Парсинг диапазона дат (сегодня, неделя, месяц, квартал, год)
        $now = Carbon::now();

        switch ($dateRange) {
            case 'week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'quarter':
                return [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()];
            case 'year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
            default:
                return [$now->copy()->subDays(30), $now];
        }
    }
}
