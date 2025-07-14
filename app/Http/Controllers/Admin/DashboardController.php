<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\User;
use App\Models\News;

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
            'last_news' => News::latest()->take(5)->get()
        ];
        
        return view('admin.dashboard', compact('stats'));
    }
}