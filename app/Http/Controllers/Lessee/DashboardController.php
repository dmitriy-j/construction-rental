<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Auth;

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

        // Финансовая информация
        $balance = $balanceService->getCurrentBalance($company);
        $recentTransactions = $balanceService->getTransactionHistory($company, 5);

        // Данные для графика расходов
        $expenseCategories = ['Аренда техники', 'Доставка', 'Прочее'];
        $expenseAmounts = [150000, 25000, 10000];

        return view('lessee.dashboard', compact(
            'stats',
            'recentOrders',
            'upcomingReturns',
            'balance',
            'recentTransactions',
            'expenseCategories',
            'expenseAmounts'
        ));
    }

    public function orders()
    {
        $orders = Order::where('lessee_company_id', auth()->user()->company_id)
            ->with(['lessorCompany', 'childOrders.items']) // Загружаем дочерние заказы и их позиции
            ->orderBy('created_at', 'desc') // Сортировка от новых к старым
            ->paginate(10);

        return view('lessee.orders.index', compact('orders'));
    }

    public function documents()
    {
        // Логика для документов арендатора
        return view('lessee.documents.index');
    }
}
