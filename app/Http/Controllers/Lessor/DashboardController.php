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
        
        $stats = [
            'equipment_count' => Equipment::where('company_id', $companyId)->count(),
            'pending_orders' => Order::where('lessor_company_id', $companyId)
                                    ->where('status', 'pending')
                                    ->count(),
            'active_orders' => Order::where('lessor_company_id', $companyId)
                                   ->where('status', 'active')
                                   ->count(),
            'revenue' => Order::where('lessor_company_id', $companyId)
                             ->where('status', 'completed')
                             ->sum('total_amount')
        ];

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

    public function equipment()
    {
        $equipment = Equipment::with('category', 'location')
            ->where('company_id', Auth::user()->company_id)
            ->paginate(10);

        return view('lessor.equipment.index', compact('equipment'));
    }

    public function orders(Request $request)
    {
        $status = $request->input('status');
        $companyId = Auth::user()->company_id;
        
        $orders = Order::with(['lesseeCompany', 'items.equipment'])
            ->where('lessor_company_id', $companyId)
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('lessor.orders.index', compact('orders'));
    }
    public function documents()
    {
        // Логика для документов арендодателя
        return view('lessor.documents.index');
    }
}