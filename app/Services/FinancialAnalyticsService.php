<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;

class FinancialAnalyticsService
{
    public function getPlatformRevenue($startDate, $endDate)
    {
        return TransactionEntry::where('purpose', 'platform_fee')
            ->where('is_canceled', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    public function getTurnoverByCompanyType($startDate, $endDate)
    {
        return TransactionEntry::join('companies', 'transaction_entries.company_id', '=', 'companies.id')
            ->where('transaction_entries.is_canceled', false)
            ->whereBetween('transaction_entries.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('CASE
                    WHEN companies.is_lessor = true THEN "Арендодатели"
                    WHEN companies.is_lessee = true THEN "Арендаторы"
                    ELSE "Другие"
                END as company_type'),
                DB::raw('SUM(transaction_entries.amount) as total')
            )
            ->groupBy('company_type')
            ->get();
    }

    public function getMonthlyGrowth($months = 12)
    {
        $results = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = now()->subMonths($i)->startOfMonth();
            $endDate = now()->subMonths($i)->endOfMonth();
            $previousStartDate = now()->subMonths($i + 1)->startOfMonth();
            $previousEndDate = now()->subMonths($i + 1)->endOfMonth();

            $currentRevenue = $this->getPlatformRevenue($startDate, $endDate);
            $previousRevenue = $this->getPlatformRevenue($previousStartDate, $previousEndDate);

            $growth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

            $results[] = [
                'month' => $startDate->format('Y-m'),
                'revenue' => $currentRevenue,
                'growth' => $growth,
            ];
        }

        return $results;
    }

    public function getTopPerformingCompanies($limit = 10, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?: now()->subMonth()->startOfMonth();
        $endDate = $endDate ?: now()->subMonth()->endOfMonth();

        return Company::withSum(['transactions' => function ($query) use ($startDate, $endDate) {
            $query->where('is_canceled', false)
                ->whereBetween('created_at', [$startDate, $endDate]);
        }], 'amount')
            ->orderBy('transactions_sum_amount', 'desc')
            ->limit($limit)
            ->get();
    }
}
