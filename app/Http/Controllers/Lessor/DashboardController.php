<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TransactionEntry;
use App\Models\EquipmentRentalTerm;
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
        $userId = Auth::id();
        $company = Auth::user()->company;

        $stats = [
            'equipment_count' => Equipment::where('company_id', $companyId)->count(),
            'pending_orders' => Order::where('lessor_company_id', $companyId)
                ->where('status', Order::STATUS_PENDING_APPROVAL)
                ->count(),
            'active_orders' => Order::where('lessor_company_id', $companyId)
                ->where('status', Order::STATUS_ACTIVE)
                ->count(),
            'revenue' => Order::where('lessor_company_id', $companyId)
                ->where('status', Order::STATUS_COMPLETED)
                ->sum('total_amount'),
        ];

        if ($stats['pending_orders'] > 0) {
            session()->put("pending_orders_{$userId}", $stats['pending_orders']);
        }

        $recentOrders = Order::with('lesseeCompany')
            ->where('lessor_company_id', $companyId)
            ->latest()
            ->take(5)
            ->get();

        $featuredEquipment = Equipment::where('company_id', $companyId)
            ->where('is_featured', true)
            ->with(['images', 'category'])
            ->limit(5)
            ->get();

        $balance = $balanceService->getCurrentBalance($company);
        $recentTransactions = $balanceService->getTransactionHistory($company, 5);

        return view('lessor.dashboard', compact(
            'stats',
            'recentOrders',
            'featuredEquipment',
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

        $cacheKey = 'lessor_dashboard_' . $companyId . '_' . md5($startDate . '_' . $endDate);

        return Cache::remember($cacheKey, 300, function () use ($companyId, $company, $balanceService, $startDate, $endDate) {
            // KPI
            $balance = $balanceService->getCurrentBalance($company);
            $equipmentCount = Equipment::where('company_id', $companyId)->count();
            $pendingOrders = Order::where('lessor_company_id', $companyId)
                ->where('status', Order::STATUS_PENDING_APPROVAL)->count();
            $activeOrders = Order::where('lessor_company_id', $companyId)
                ->where('status', Order::STATUS_ACTIVE)->count();
            $completedOrders = Order::where('lessor_company_id', $companyId)
                ->where('status', Order::STATUS_COMPLETED)->count();
            $revenue = Order::where('lessor_company_id', $companyId)
                ->where('status', Order::STATUS_COMPLETED)
                ->sum('total_amount');

            // Доход по дням/месяцам
            $incomeByPeriod = OrderItem::whereHas('order', function ($q) use ($companyId, $startDate, $endDate) {
                $q->where('lessor_company_id', $companyId)
                    ->where('status', Order::STATUS_COMPLETED);
            })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as date, SUM(total_price) as total")
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $incomeLabels = $incomeByPeriod->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d.m'))->toArray();
            $incomeData = $incomeByPeriod->pluck('total')->toArray();

            // Если данных мало - группируем по месяцам
            if (count($incomeLabels) <= 3) {
                $incomeByPeriod = OrderItem::whereHas('order', function ($q) use ($companyId, $startDate, $endDate) {
                    $q->where('lessor_company_id', $companyId)
                        ->where('status', Order::STATUS_COMPLETED);
                })
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as date, SUM(total_price) as total")
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                $incomeLabels = $incomeByPeriod->pluck('date')->toArray();
                $incomeData = $incomeByPeriod->pluck('total')->toArray();
            }

            // Загрузка техники (количество заказов на единицу техники)
            $equipmentUsage = OrderItem::whereHas('order', function ($q) use ($companyId) {
                $q->where('lessor_company_id', $companyId);
            })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("equipment_id, SUM(quantity) as total_used")
                ->with('equipment:id,title')
                ->groupBy('equipment_id')
                ->orderByDesc('total_used')
                ->take(10)
                ->get();

            $usageLabels = $equipmentUsage->pluck('equipment.title')->toArray();
            $usageData = $equipmentUsage->pluck('total_used')->toArray();

            // Доход от комиссий (transaction entries)
            $incomeFromTransactions = TransactionEntry::where('company_id', $company->id)
                ->where('is_canceled', false)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("DATE(created_at) as date, SUM(amount) as total")
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $transIncomeLabels = $incomeFromTransactions->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d.m'))->toArray();
            $transIncomeData = $incomeFromTransactions->pluck('total')->toArray();

            // Топ-5 самого востребованного оборудования
            $topEquipment = OrderItem::whereHas('order', function ($q) use ($companyId) {
                $q->where('lessor_company_id', $companyId);
            })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("equipment_id, SUM(quantity) as total_used, SUM(total_price) as total_revenue")
                ->with('equipment:id,title')
                ->groupBy('equipment_id')
                ->orderByDesc('total_used')
                ->take(5)
                ->get()
                ->map(fn($item) => [
                    'title' => $item->equipment?->title ?? 'N/A',
                    'used' => (int) $item->total_used,
                    'revenue' => (float) $item->total_revenue,
                ]);

            // Последние заказы
            $recentOrders = Order::where('lessor_company_id', $companyId)
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'amount' => (float) ($order->lessor_base_amount + $order->delivery_cost),
                    'status' => $order->status,
                    'status_text' => Order::statusText($order->status),
                    'date' => $order->created_at->format('d.m.Y'),
                    'start_date' => $order->start_date?->format('d.m.Y'),
                    'end_date' => $order->end_date?->format('d.m.Y'),
                ]);

            return [
                'kpi' => [
                    ['title' => 'Текущий баланс', 'value' => number_format($balance, 2) . ' ₽', 'color' => 'info', 'icon' => 'bi-wallet2'],
                    ['title' => 'Техники', 'value' => $equipmentCount, 'color' => 'primary', 'icon' => 'bi-tools'],
                    ['title' => 'Новые заказы', 'value' => $pendingOrders, 'color' => 'warning', 'icon' => 'bi-bell'],
                    ['title' => 'Активные заказы', 'value' => $activeOrders, 'color' => 'success', 'icon' => 'bi-play-circle'],
                    ['title' => 'Завершённые', 'value' => $completedOrders, 'color' => 'secondary', 'icon' => 'bi-check-circle'],
                    ['title' => 'Выручка', 'value' => number_format($revenue, 0) . ' ₽', 'color' => 'success', 'icon' => 'bi-currency-ruble'],
                ],
                'charts' => [
                    'income' => [
                        'labels' => $incomeLabels ?: $transIncomeLabels,
                        'datasets' => [
                            [
                                'label' => 'Доход',
                                'data' => $incomeData ?: $transIncomeData,
                                'borderColor' => '#198754',
                                'backgroundColor' => 'rgba(25, 135, 84, 0.1)',
                            ],
                        ],
                    ],
                    'equipmentUsage' => [
                        'labels' => $usageLabels,
                        'datasets' => [
                            [
                                'label' => 'Загрузка техники',
                                'data' => $usageData,
                                'backgroundColor' => ['#0d6efd', '#ffc107', '#198754', '#dc3545', '#0dcaf0', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#adb5bd'],
                            ],
                        ],
                    ],
                    'incomeFromTransactions' => [
                        'labels' => $transIncomeLabels,
                        'datasets' => [
                            [
                                'label' => 'Поступления',
                                'data' => $transIncomeData,
                                'borderColor' => '#0d6efd',
                                'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
                            ],
                        ],
                    ],
                ],
                'topEquipment' => $topEquipment,
                'recentOrders' => $recentOrders,
                'period' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d'),
                ],
            ];
        });
    }

    public function markAsViewed(Request $request)
    {
        $userId = Auth::id();
        session()->forget("pending_orders_{$userId}");

        return response()->json(['success' => true]);
    }

    public function orders(Request $request)
    {
        $status = $request->input('status');
        $companyId = Auth::user()->company_id;

        $orders = Order::with(['items.equipment'])
            ->where('lessor_company_id', $companyId)
            ->whereNotNull('parent_order_id')
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('lessor.orders.index', compact('orders'));
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
