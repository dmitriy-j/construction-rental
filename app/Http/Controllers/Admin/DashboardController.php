<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\News;
use App\Models\Order;
use App\Models\TransactionEntry;
use App\Models\Upd;
use App\Models\User;

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
            'draft_news' => News::where('is_published', false)->count(),
            'last_news' => News::latest()->take(5)->get(),
        ];

        $stats['unapproved_equipment'] = Equipment::where('is_approved', false)->count();

        // Финансовая статистика
        $financialStats = [
            'total_turnover' => TransactionEntry::sum('amount'),
            'platform_revenue' => TransactionEntry::where('purpose', 'platform_fee')->sum('amount'),
            'pending_upds' => Upd::where('status', 'pending')->count(),
            'recent_payments' => TransactionEntry::where('created_at', '>=', now()->subDays(7))->count(),
            'chart_labels' => ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
            'chart_data' => [50000, 75000, 60000, 90000, 110000, 95000],
        ];

        return view('admin.dashboard', compact('stats', 'financialStats'));
    }
}
