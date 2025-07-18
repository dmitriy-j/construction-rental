<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $userId = Auth::id();

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
                             ->sum('total_amount')
        ];

        // Сохраняем количество новых заказов в сессии для мигания
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

        return view('lessor.dashboard', compact('stats', 'recentOrders', 'featuredEquipment'));
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
            ->whereNotNull('parent_order_id') // Добавляем проверку на дочерние заказы
            ->when($status, function ($query, $status) {
                // Исправляем фильтрацию по статусу
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('lessor.orders.index', compact('orders'));
    }

}
