<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\TransactionEntry;
use App\Models\Upd;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index()
    {
        $companies = Company::where('is_lessee', true)->orWhere('is_lessor', true)->get();

        return view('admin.finance.reports', compact('companies'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'required|in:turnover,profit,invoices,upds,company_balance',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        switch ($request->report_type) {
            case 'turnover':
                $data = $this->generateTurnoverReport($startDate, $endDate, $request->company_id);
                break;
            case 'profit':
                $data = $this->generateProfitReport($startDate, $endDate, $request->company_id);
                break;
            case 'invoices':
                $data = $this->generateInvoicesReport($startDate, $endDate, $request->company_id);
                break;
            case 'upds':
                $data = $this->generateUpdsReport($startDate, $endDate, $request->company_id);
                break;
            case 'company_balance':
                $data = $this->generateCompanyBalanceReport($startDate, $endDate, $request->company_id);
                break;
            default:
                return back()->with('error', 'Неизвестный тип отчета');
        }

        return view('admin.finance.report-result', compact('data', 'request'));
    }

    private function generateTurnoverReport($startDate, $endDate, $companyId = null)
    {
        $query = TransactionEntry::whereBetween('created_at', [$startDate, $endDate])
            ->where('is_canceled', false);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->select(
            DB::raw('DATE(created_at) as date'),
            'purpose',
            'type',
            DB::raw('SUM(amount) as total')
        )
            ->groupBy('date', 'purpose', 'type')
            ->orderBy('date')
            ->get();
    }

    private function generateProfitReport($startDate, $endDate, $companyId = null)
    {
        // Прибыль платформы - комиссия за вычетом расходов
        $platformFee = TransactionEntry::whereBetween('created_at', [$startDate, $endDate])
            ->where('purpose', 'platform_fee')
            ->where('is_canceled', false)
            ->sum('amount');

        $expenses = TransactionEntry::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'credit')
            ->where('purpose', '!=', 'platform_fee')
            ->where('is_canceled', false)
            ->sum('amount');

        return [
            'platform_fee' => $platformFee,
            'expenses' => $expenses,
            'profit' => $platformFee - $expenses,
        ];
    }

    private function generateInvoicesReport($startDate, $endDate, $companyId = null)
    {
        $query = Invoice::whereBetween('created_at', [$startDate, $endDate]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->with('company')
            ->orderBy('created_at')
            ->get();
    }

    private function generateUpdsReport($startDate, $endDate, $companyId = null)
    {
        $query = Upd::whereBetween('created_at', [$startDate, $endDate]);

        if ($companyId) {
            $query->where('lessor_company_id', $companyId)
                ->orWhere('lessee_company_id', $companyId);
        }

        return $query->with(['lessorCompany', 'lesseeCompany'])
            ->orderBy('created_at')
            ->get();
    }

    private function generateCompanyBalanceReport($startDate, $endDate, $companyId = null)
    {
        $query = Company::query();

        if ($companyId) {
            $query->where('id', $companyId);
        }

        return $query->with(['transactions' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate])
                ->where('is_canceled', false);
        }])->get();
    }

    public function export(Request $request)
    {
        // Реализация экспорта отчета в Excel/PDF
    }
}
