<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TransactionEntry;
use App\Services\BalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(BalanceService $balanceService)
    {
        $companyId = Auth::user()->company_id;
        $company = Auth::user()->company;

        $stats = [
            'active_orders' => Order::where('lessee_company_id', $companyId)
                ->where('status', 'active')
                ->count(),
            'pending_orders' => Order::where('lessee_company_id', $companyId)
                ->where('status', 'pending')
                ->count(),
            'completed_orders' => Order::where('lessee_company_id', $companyId)
                ->where('status', 'completed')
                ->count(),
            'total_spent' => Order::where('lessee_company_id', $companyId)
                ->where('status', 'completed')
                ->sum('total_amount'),
        ];

        $recentOrders = Order::with('lessorCompany')
            ->where('lessee_company_id', $companyId)
            ->latest()
            ->take(5)
            ->get();

        $upcomingReturns = Order::where('lessee_company_id', $companyId)
            ->where('status', Order::STATUS_ACTIVE)
            ->where('end_date', '<=', now()->addDays(3))
            ->with('lessorCompany')
            ->limit(5)
            ->get();

        $balance = $balanceService->getCurrentBalance($company);
        $recentTransactions = $balanceService->getTransactionHistory($company, 5);

        return view('lessee.dashboard', compact(
            'stats',
            'recentOrders',
            'upcomingReturns',
            'balance',
            'recentTransactions',
        ));
    }

    public function getData(Request $request, BalanceService $balanceService)
    {
        $companyId = Auth::user()->company_id;
        $company = Auth::user()->company;

        $period = $request->input('period', 'month');
        $from = $request->input('from');
        $to = $request->input('to');

        [$startDate, $endDate] = $this->parseDateRange($period, $from, $to);

        $cacheKey = 'lessee_dashboard_' . $companyId . '_' . md5($startDate . '_' . $endDate);

        return Cache::remember($cacheKey, 300, function () use ($companyId, $company, $balanceService, $startDate, $endDate) {
            // KPI
            $balance = $balanceService->getCurrentBalance($company);
            $activeOrders = Order::where('lessee_company_id', $companyId)
                ->where('status', Order::STATUS_ACTIVE)->count();
            $pendingOrders = Order::where('lessee_company_id', $companyId)
                ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PENDING_APPROVAL])->count();
            $completedOrders = Order::where('lessee_company_id', $companyId)
                ->where('status', Order::STATUS_COMPLETED)->count();
            $totalSpent = Order::where('lessee_company_id', $companyId)
                ->where('status', Order::STATUS_COMPLETED)
                ->sum('total_amount');

            // Расходы по месяцам
            $monthlyExpenses = OrderItem::whereHas('order', function ($q) use ($companyId) {
                $q->where('lessee_company_id', $companyId);
            })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_price) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $expenseLabels = $monthlyExpenses->pluck('month')->toArray();
            $expenseData = $monthlyExpenses->pluck('total')->toArray();

            // Расходы по типам техники (круговая)
            $expensesByCategory = OrderItem::whereHas('order', function ($q) use ($companyId) {
                $q->where('lessee_company_id', $companyId);
            })
                ->whereHas('equipment.category')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("SUM(total_price) as total")
                ->groupBy('equipment_id')
                ->with('equipment.category:id,name')
                ->get()
                ->groupBy(fn($item) => $item->equipment?->category?->name ?? 'Без категории')
                ->map(fn($items) => $items->sum('total'));

            $categoryLabels = $expensesByCategory->keys()->toArray();
            $categoryData = $expensesByCategory->values()->toArray();

            // Баланс (история)
            $balanceHistory = TransactionEntry::where('company_id', $company->id)
                ->where('is_canceled', false)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at')
                ->get()
                ->map(fn($t) => [
                    'date' => $t->created_at->format('d.m'),
                    'amount' => (float) $t->amount,
                    'type' => $t->type,
                    'balance' => (float) $t->balance_snapshot,
                ]);

            // Последние заказы
            $recentOrders = Order::where('lessee_company_id', $companyId)
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'status_text' => Order::statusText($order->status),
                    'date' => $order->created_at->format('d.m.Y'),
                    'end_date' => $order->end_date?->format('d.m.Y'),
                ]);

            // Ближайшие возвраты
            $upcomingReturns = Order::where('lessee_company_id', $companyId)
                ->where('status', Order::STATUS_ACTIVE)
                ->where('end_date', '<=', now()->addDays(3))
                ->limit(5)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'end_date' => $order->end_date?->format('d.m.Y'),
                ]);

            return [
                'kpi' => [
                    ['title' => 'Текущий баланс', 'value' => number_format($balance, 2) . ' ₽', 'color' => 'info', 'icon' => 'bi-wallet2'],
                    ['title' => 'Активные заказы', 'value' => $activeOrders, 'color' => 'success', 'icon' => 'bi-play-circle'],
                    ['title' => 'Ожидающие', 'value' => $pendingOrders, 'color' => 'warning', 'icon' => 'bi-hourglass'],
                    ['title' => 'Завершённые', 'value' => $completedOrders, 'color' => 'secondary', 'icon' => 'bi-check-circle'],
                    ['title' => 'Всего потрачено', 'value' => number_format($totalSpent, 0) . ' ₽', 'color' => 'primary', 'icon' => 'bi-currency-ruble'],
                ],
                'charts' => [
                    'expenses' => [
                        'labels' => $expenseLabels,
                        'datasets' => [
                            [
                                'label' => 'Расходы',
                                'data' => $expenseData,
                                'borderColor' => '#0d6efd',
                                'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
                            ],
                        ],
                    ],
                    'expensesByCategory' => [
                        'labels' => $categoryLabels,
                        'datasets' => [
                            [
                                'label' => 'Расходы по категориям',
                                'data' => $categoryData,
                                'backgroundColor' => ['#0d6efd', '#ffc107', '#198754', '#dc3545', '#0dcaf0', '#6f42c1', '#fd7e14'],
                            ],
                        ],
                    ],
                    'balanceHistory' => [
                        'labels' => $balanceHistory->pluck('date')->toArray(),
                        'datasets' => [
                            [
                                'label' => 'Баланс',
                                'data' => $balanceHistory->pluck('balance')->toArray(),
                                'borderColor' => '#198754',
                                'backgroundColor' => 'rgba(25, 135, 84, 0.1)',
                            ],
                        ],
                    ],
                ],
                'recentOrders' => $recentOrders,
                'upcomingReturns' => $upcomingReturns,
                'period' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d'),
                ],
            ];
        });
    }

    private function parseDateRange(string $period, ?string $from, ?string $to): array
    {
        if ($from && $to) {
            return [Carbon::parse($from), Carbon::parse($to)->endOfDay()];
        }

        $end = now()->endOfDay();

        $start = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek()->startOfDay(),
            'month' => now()->subMonth()->startOfDay(),
            'year' => now()->subYear()->startOfDay(),
            default => now()->subMonth()->startOfDay(),
        };

        return [$start, $end];
    }
}
