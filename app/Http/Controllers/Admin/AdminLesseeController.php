<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLesseeController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::where('is_lessee', true)
            ->withCount('equipment');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('legal_name', 'like', "%$search%")
                    ->orWhere('inn', 'like', "%$search%")
                    ->orWhere('director_name', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $lessees = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

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

    public function edit(Company $lessee)
    {
        abort_unless($lessee->is_lessee, 404);

        return view('admin.lessees.edit', [
            'lessee' => $lessee,
        ]);
    }

    public function update(Request $request, Company $lessee)
    {
        abort_unless($lessee->is_lessee, 404);

        $validated = $request->validate([
            'legal_name' => 'required|string|max:255',
            'inn' => 'required|string|max:12',
            'kpp' => 'nullable|string|max:9',
            'ogrn' => 'nullable|string|max:15',
            'okpo' => 'nullable|string|max:10',
            'legal_address' => 'nullable|string|max:500',
            'actual_address' => 'nullable|string|max:500',
            'director_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'contacts' => 'nullable|string|max:500',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:20',
            'bik' => 'nullable|string|max:9',
            'correspondent_account' => 'nullable|string|max:20',
            'legal_type' => 'nullable|string|in:ooo,ip',
        ]);

        $lessee->update($validated);

        return redirect()->route('admin.lessees.show', $lessee)
            ->with('success', 'Данные арендатора обновлены!');
    }

    public function verify(Company $lessee, Request $request)
    {
        abort_unless($lessee->is_lessee, 404);

        $request->validate([
            'action' => 'required|in:verify,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            if ($request->action === 'verify') {
                $lessee->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                    'rejection_reason' => null,
                ]);
            } else {
                $lessee->update([
                    'status' => 'rejected',
                    'verified_at' => null,
                    'rejection_reason' => $request->rejection_reason,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Ошибка: '.$e->getMessage()]);
        }

        return redirect()->route('admin.lessees.show', $lessee)
            ->with('success', $request->action === 'verify' ? 'Арендатор подтверждён!' : 'Арендатор отклонён!');
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
