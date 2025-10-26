<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminLesseeController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::where('is_lessee', true)
            ->withCount('equipment');

        // Фильтрация
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('legal_name', 'like', "%$search%")
                    ->orWhere('inn', 'like', "%$search%")
                    ->orWhere('director_name', 'like', "%$search%");
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $lessees = $query->paginate(25)->withQueryString();

        return view('admin.lessees.index', [
            'lessees' => $lessees,
            'statuses' => ['pending', 'verified', 'rejected'],
        ]);
    }

    public function show(Company $lessee)
    {
        abort_unless($lessee->is_lessee, 404);

        $orders = Order::where('lessee_company_id', $lessee->id)
            ->with(['items.equipment', 'lessorCompany'])
            ->latest()
            ->paginate(10);

        return view('admin.lessees.show', [
            'lessee' => $lessee,
            'orders' => $orders,
            'statuses' => Order::statuses(),
        ]);
    }

    public function showOrder(Company $lessee, Order $order)
    {
        abort_unless($lessee->is_lessee, 404);
        abort_unless($order->lessee_company_id == $lessee->id, 404);

        $order->load([
            'items.equipment.company',
            'items.rentalTerm',
            'lessorCompany',
            'waybills',
            'deliveryNote',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'lessee' => $lessee,
        ]);
    }
}
