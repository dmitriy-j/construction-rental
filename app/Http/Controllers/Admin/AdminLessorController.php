<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminLessorController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::where('is_lessor', true)
            ->withCount('equipment');

        if ($search = $request->search) {
            $query->where(function($q) use ($search) {
                $q->where('legal_name', 'like', "%$search%")
                  ->orWhere('inn', 'like', "%$search%")
                  ->orWhere('director_name', 'like', "%$search%");
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $lessors = $query->paginate(25)->withQueryString();

        return view('admin.lessors.index', [
            'lessors' => $lessors,
            'statuses' => ['pending', 'verified', 'rejected']
        ]);
    }

    public function show(Company $lessor)
{
    abort_unless($lessor->is_lessor, 404);

    // Загружаем технику с пагинацией и условиями аренды
    $equipment = $lessor->equipment()
        ->with(['rentalTerms', 'category'])
        ->latest()
        ->paginate(10);

    return view('admin.lessors.show', [
        'lessor' => $lessor,
        'equipment' => $equipment, // Передаем пагинированный список
        'statuses' => ['pending', 'verified', 'rejected']
    ]);
}

    public function showOrder(Company $lessor, Order $order)
    {
        abort_unless($lessor->is_lessor, 404);
        abort_unless($order->lessor_company_id == $lessor->id, 404);

        $order->load([
            'items.equipment.company',
            'items.rentalTerm',
            'lesseeCompany',
            'waybills',
            'deliveryNote'
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'lessor' => $lessor
        ]);
    }
}