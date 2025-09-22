<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\TransactionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function balance()
    {
        $company = Auth::user()->company;
        $balance = app(\App\Services\BalanceService::class)->getCurrentBalance($company);

        return response()->json([
            'company' => $company->legal_name,
            'balance' => $balance,
            'currency' => 'RUB',
        ]);
    }

    public function transactions(Request $request)
    {
        $company = Auth::user()->company;
        $perPage = $request->get('per_page', 20);

        $transactions = TransactionEntry::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($transactions);
    }

    public function invoices(Request $request)
    {
        $company = Auth::user()->company;
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');

        $query = Invoice::where('company_id', $company->id);

        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($invoices);
    }

    public function reconciliationActs(Request $request)
    {
        $company = Auth::user()->company;
        $perPage = $request->get('per_page', 10);

        $acts = \App\Models\ReconciliationAct::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($acts);
    }
}
