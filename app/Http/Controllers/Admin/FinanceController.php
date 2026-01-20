<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\TransactionEntry;
use App\Models\Upd;
use App\Services\BalanceService;
use App\Services\FinancialAnalyticsService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function dashboard()
    {
        $totalTurnover = TransactionEntry::where('is_canceled', false)->sum('amount');
        $platformRevenue = TransactionEntry::where('purpose', 'platform_fee')
            ->where('is_canceled', false)
            ->sum('amount');
        $pendingUpdsCount = Upd::where('status', 'pending')->count();
        $recentPaymentsCount = TransactionEntry::where('purpose', 'lessee_payment')
            ->where('created_at', '>=', now()->subDays(7))
            ->where('is_canceled', false)
            ->count();

        $transactionTypes = TransactionEntry::where('is_canceled', false)
            ->select('purpose', \DB::raw('SUM(amount) as total'))
            ->groupBy('purpose')
            ->get();

        $transactionTypesData = [
            'labels' => $transactionTypes->pluck('purpose')->toArray(),
            'data' => $transactionTypes->pluck('total')->toArray(),
        ];

        $topCompanies = \App\Models\Company::withSum(['transactions' => function ($query) {
            $query->where('is_canceled', false);
        }], 'amount')
            ->orderBy('transactions_sum_amount', 'desc')
            ->limit(10)
            ->get()
            ->each(function ($company) {
                $company->turnover = $company->transactions_sum_amount;
            });

        $recentTransactions = TransactionEntry::with('company')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $analyticsService = new FinancialAnalyticsService;

        // Добавляем новые метрики
        $monthlyRevenue = $analyticsService->getPlatformRevenue(now()->startOfMonth(), now());
        $previousMonthRevenue = $analyticsService->getPlatformRevenue(
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        );

        $revenueGrowth = $previousMonthRevenue > 0
            ? (($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
            : 0;

        $monthlyGrowth = $analyticsService->getMonthlyGrowth(6);
        $topCompanies = $analyticsService->getTopPerformingCompanies(5);

        return view('admin.finance.dashboard', compact(
            'totalTurnover',
            'platformRevenue',
            'pendingUpdsCount',
            'recentPaymentsCount',
            'transactionTypesData',
            'topCompanies',
            'recentTransactions',
            'monthlyRevenue',
            'revenueGrowth',
            'monthlyGrowth',
        ));
    }

    public function transactions(Request $request)
    {
        $query = TransactionEntry::with('company')->latest();

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        $transactions = $query->paginate(50);
        $companies = \App\Models\Company::all();

        return view('admin.finance.transactions', compact('transactions', 'companies'));
    }

    public function showTransaction(TransactionEntry $transaction)
    {
        $transaction->load('company', 'source');

        return view('admin.finance.transaction-show', compact('transaction'));
    }

    public function cancelTransaction(Request $request, TransactionEntry $transaction)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction->cancel($request->reason);

            // Создаем обратную проводку
            app(BalanceService::class)->commitTransaction(
                $transaction->company,
                $transaction->amount,
                $transaction->type === 'debit' ? 'credit' : 'debit',
                'reversal',
                $transaction,
                "Отмена проводки #{$transaction->id}. Причина: {$request->reason}",
                'reversal_'.$transaction->id.'_'.time()
            );

            DB::commit();

            return redirect()->route('admin.finance.transactions')
                ->with('success', 'Транзакция успешно отменена');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Ошибка: '.$e->getMessage());
        }
    }

    public function invoices()
    {
        $invoices = Invoice::with(['company', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.finance.invoices', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice)
    {
        $invoice->load([
            'company',
            'order.items.equipment', // Загружаем позиции заказа с оборудованием
        ]);

        return view('admin.finance.invoice-show', compact('invoice'));
    }

    public function bankStatements()
    {
        $statements = BankStatement::with('processedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.finance.bank-statements', compact('statements'));
    }

    public function processBankStatement(Request $request)
    {
        $request->validate([
            'statement' => 'required|file|mimes:txt,xml,1c',
            'bank_name' => 'required|string',
        ]);

        try {
            $file = $request->file('statement');
            $content = file_get_contents($file->getRealPath());

            $parser = new BankStatementParser;
            $transactions = $parser->parse($content);

            $statement = BankStatement::create([
                'filename' => $file->getClientOriginalName(),
                'bank_name' => $request->bank_name,
                'transactions_count' => count($transactions),
                'processed_by' => auth()->id(),
                'status' => 'processing',
            ]);

            ProcessBankStatement::dispatch($statement, $transactions);

            return redirect()->back()->with('success', 'Выписка принята в обработку');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка: '.$e->getMessage());
        }
    }
}
