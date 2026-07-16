<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLessorController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::where('is_lessor', true)
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

        $lessors = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        return view('admin.lessors.index', [
            'lessors' => $lessors,
            'statuses' => ['pending', 'verified', 'rejected'],
        ]);
    }

    public function show(Company $lessor)
    {
        abort_unless($lessor->is_lessor, 404);

        $equipment = $lessor->equipment()
            ->with(['rentalTerms', 'category'])
            ->latest()
            ->paginate(10);

        return view('admin.lessors.show', [
            'lessor' => $lessor,
            'equipment' => $equipment,
            'statuses' => ['pending', 'verified', 'rejected'],
        ]);
    }

    public function edit(Company $lessor)
    {
        abort_unless($lessor->is_lessor, 404);

        return view('admin.lessors.edit', [
            'lessor' => $lessor,
        ]);
    }

    public function update(Request $request, Company $lessor)
    {
        abort_unless($lessor->is_lessor, 404);

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

        $lessor->update($validated);

        return redirect()->route('admin.lessors.show', $lessor)
            ->with('success', 'Данные арендодателя обновлены!');
    }

    public function verify(Company $lessor, Request $request)
    {
        abort_unless($lessor->is_lessor, 404);

        $request->validate([
            'action' => 'required|in:verify,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            if ($request->action === 'verify') {
                $lessor->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                    'rejection_reason' => null,
                ]);
            } else {
                $lessor->update([
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

        return redirect()->route('admin.lessors.show', $lessor)
            ->with('success', $request->action === 'verify' ? 'Арендодатель подтверждён!' : 'Арендодатель отклонён!');
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
            'deliveryNote',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'lessor' => $lessor,
        ]);
    }
}
