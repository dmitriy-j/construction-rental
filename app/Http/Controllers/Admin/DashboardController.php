<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\News;
use App\Models\Order;
use App\Models\TransactionEntry;
use App\Models\Upd;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'equipment_count' => Equipment::count(),
            'orders_count' => Order::count(),
            'users_count' => User::count(),
            'total_news' => News::count(),
            'published_news' => News::published()->count(),
            'draft_news' => News::where('is_active', false)->count(),
            'last_news' => News::latest()->take(5)->get(),
        ];

        $stats['unapproved_equipment'] = Equipment::where('is_approved', false)->count();

        return view('admin.dashboard', compact('stats'));
    }

    public function getData(Request $request)
    {
        $period = $request->input('period', 'month');
        $from = $request->input('from');
        $to = $request->input('to');

        [$startDate, $endDate] = $this->parseDateRange($period, $from, $to);

        $cacheKey = 'admin_dashboard_' . md5($startDate . '_' . $endDate);

        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            // KPI
            $totalUsers = User::count();
            $lessees = Company::where('is_lessee', true)->count();
            $lessors = Company::where('is_lessor', true)->count();
            $totalOrders = Order::count();
            $activeOrders = Order::where('status', Order::STATUS_ACTIVE)->count();
            $completedOrders = Order::where('status', Order::STATUS_COMPLETED)->count();
            $pendingOrders = Order::whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PENDING_APPROVAL])->count();
            $totalEquipment = Equipment::count();
            $unapprovedEquipment = Equipment::where('is_approved', false)->count();

            // Финансы
            $totalTurnover = TransactionEntry::where('is_canceled', false)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            $platformRevenue = TransactionEntry::where('is_canceled', false)
                ->where('purpose', TransactionEntry::PURPOSE_PLATFORM_FEE)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            $pendingUpds = Upd::where('status', 'pending')->count();
            $recentPayments = TransactionEntry::where('is_canceled', false)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            // График оборота по дням
            $dailyTurnover = TransactionEntry::where('is_canceled', false)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("DATE(created_at) as date, SUM(amount) as total")
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $turnoverLabels = $dailyTurnover->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d.m'))->toArray();
            $turnoverData = $dailyTurnover->pluck('total')->toArray();

            // График комиссии по дням
            $dailyCommission = TransactionEntry::where('is_canceled', false)
                ->where('purpose', TransactionEntry::PURPOSE_PLATFORM_FEE)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("DATE(created_at) as date, SUM(amount) as total")
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $commissionLabels = $dailyCommission->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d.m'))->toArray();
            $commissionData = $dailyCommission->pluck('total')->toArray();

            // Заказы по статусам
            $ordersByStatus = Order::selectRaw("status, COUNT(*) as total")
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status')
                ->toArray();

            // Топ-5 арендодателей по доходам
            $topLessors = TransactionEntry::where('is_canceled', false)
                ->where('purpose', TransactionEntry::PURPOSE_LESSOR_PAYOUT)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("company_id, SUM(amount) as total")
                ->with('company:id,legal_name')
                ->groupBy('company_id')
                ->orderByDesc('total')
                ->take(5)
                ->get()
                ->map(fn($entry) => [
                    'name' => $entry->company?->legal_name ?? 'N/A',
                    'total' => (float) $entry->total,
                ]);

            // Топ-5 арендаторов по количеству заказов
            $topLessees = Order::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw("lessee_company_id, COUNT(*) as total")
                ->with('lesseeCompany:id,legal_name')
                ->groupBy('lessee_company_id')
                ->orderByDesc('total')
                ->take(5)
                ->get()
                ->map(fn($order) => [
                    'name' => $order->lesseeCompany?->legal_name ?? 'N/A',
                    'total' => (int) $order->total,
                ]);

            // Последние заказы
            $recentOrders = Order::with(['lesseeCompany:id,legal_name', 'lessorCompany:id,legal_name'])
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'lessee' => $order->lesseeCompany?->legal_name ?? 'N/A',
                    'lessor' => $order->lessorCompany?->legal_name ?? 'N/A',
                    'amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'status_text' => Order::statusText($order->status),
                    'date' => $order->created_at->format('d.m.Y H:i'),
                ]);

            return [
                'kpi' => [
                    ['title' => 'Всего пользователей', 'value' => $totalUsers, 'color' => 'primary', 'icon' => 'bi-people'],
                    ['title' => 'Арендаторы', 'value' => $lessees, 'color' => 'info', 'icon' => 'bi-person-check'],
                    ['title' => 'Арендодатели', 'value' => $lessors, 'color' => 'success', 'icon' => 'bi-building'],
                    ['title' => 'Всего заказов', 'value' => $totalOrders, 'color' => 'warning', 'icon' => 'bi-cart'],
                    ['title' => 'Активных заказов', 'value' => $activeOrders, 'color' => 'success', 'icon' => 'bi-play-circle'],
                    ['title' => 'Завершённых', 'value' => $completedOrders, 'color' => 'secondary', 'icon' => 'bi-check-circle'],
                    ['title' => 'Ожидает подтверждения', 'value' => $pendingOrders, 'color' => 'warning', 'icon' => 'bi-hourglass'],
                    ['title' => 'Техники', 'value' => $totalEquipment, 'color' => 'primary', 'icon' => 'bi-tools'],
                    ['title' => 'Неодобренной техники', 'value' => $unapprovedEquipment, 'color' => 'danger', 'icon' => 'bi-exclamation-triangle'],
                    ['title' => 'Оборот', 'value' => number_format($totalTurnover, 0) . ' ₽', 'color' => 'success', 'icon' => 'bi-currency-ruble'],
                    ['title' => 'Комиссия платформы', 'value' => number_format($platformRevenue, 0) . ' ₽', 'color' => 'info', 'icon' => 'bi-percent'],
                    ['title' => 'Ожидающих УПД', 'value' => $pendingUpds, 'color' => 'danger', 'icon' => 'bi-file-earmark-text'],
                    ['title' => 'Новых платежей (7д)', 'value' => $recentPayments, 'color' => 'primary', 'icon' => 'bi-credit-card'],
                ],
                'charts' => [
                    'turnover' => [
                        'labels' => $turnoverLabels,
                        'datasets' => [
                            [
                                'label' => 'Оборот',
                                'data' => $turnoverData,
                                'borderColor' => '#0d6efd',
                                'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
                            ],
                        ],
                    ],
                    'commission' => [
                        'labels' => $commissionLabels,
                        'datasets' => [
                            [
                                'label' => 'Комиссия платформы',
                                'data' => $commissionData,
                                'borderColor' => '#ffc107',
                                'backgroundColor' => 'rgba(255, 193, 7, 0.1)',
                            ],
                        ],
                    ],
                    'ordersByStatus' => [
                        'labels' => array_keys($ordersByStatus),
                        'datasets' => [
                            [
                                'label' => 'Заказы по статусам',
                                'data' => array_values($ordersByStatus),
                                'backgroundColor' => ['#0d6efd', '#ffc107', '#198754', '#6c757d', '#dc3545', '#0dcaf0'],
                            ],
                        ],
                    ],
                ],
                'topLessors' => $topLessors,
                'topLessees' => $topLessees,
                'recentOrders' => $recentOrders,
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
