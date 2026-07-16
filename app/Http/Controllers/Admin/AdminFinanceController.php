<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceAdjustment;
use App\Models\Company;
use App\Models\TransactionEntry;
use App\Models\Upd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFinanceController extends Controller
{
    /**
     * Сводка по задолженностям арендаторов на основе УПД
     */
    public function lesseeDebts(Request $request)
    {
        $query = Company::where('is_lessee', true);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('legal_name', 'like', "%$search%")
                    ->orWhere('inn', 'like', "%$search%");
            });
        }

        $lessees = $query->get()->map(function ($company) {
            // Исходящие УПД (платформа → арендатор) — начисления арендатору
            $outgoingUpds = Upd::where('lessee_company_id', $company->id)
                ->where('type', Upd::TYPE_OUTGOING)
                ->whereIn('status', ['processed', 'accepted'])
                ->get();

            $totalAccrued = $outgoingUpds->sum('total_amount');

            // Оплаты арендатора — транзакции с purpose = lessee_payment
            $totalPaid = TransactionEntry::where('company_id', $company->id)
                ->where('purpose', TransactionEntry::PURPOSE_LESSEE_PAYMENT)
                ->active()
                ->sum('amount');

            // Ручные корректировки
            $adjustments = BalanceAdjustment::where('company_id', $company->id)->get();
            $totalAdjustments = $adjustments->sum('signed_amount');

            $debt = $totalAccrued - $totalPaid + $totalAdjustments;

            // УПД без оплат (для просрочки)
            $overdueUpds = $outgoingUpds->filter(function ($upd) {
                $paid = TransactionEntry::where('company_id', $upd->lessee_company_id)
                    ->where('purpose', TransactionEntry::PURPOSE_LESSEE_PAYMENT)
                    ->where('source_type', Upd::class)
                    ->where('source_id', $upd->id)
                    ->active()
                    ->sum('amount');
                return $paid < $upd->total_amount && $upd->issue_date < now()->subDays(15);
            });

            return (object) [
                'id' => $company->id,
                'legal_name' => $company->legal_name,
                'inn' => $company->inn,
                'phone' => $company->phone,
                'total_accrued' => $totalAccrued,
                'total_paid' => $totalPaid,
                'total_adjustments' => $totalAdjustments,
                'total_debt' => max(0, $debt),
                'overdue_debt' => $overdueUpds->sum('total_amount'),
                'company' => $company,
            ];
        })->sortByDesc('total_debt');

        $totals = [
            'total_debt' => $lessees->sum('total_debt'),
            'total_accrued' => $lessees->sum('total_accrued'),
            'overdue_debt' => $lessees->sum('overdue_debt'),
        ];

        return view('admin.finance.lessee-debts', compact('lessees', 'totals'));
    }

    /**
     * Сводка по задолженностям перед арендодателями на основе УПД
     */
    public function lessorDebts(Request $request)
    {
        $query = Company::where('is_lessor', true);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('legal_name', 'like', "%$search%")
                    ->orWhere('inn', 'like', "%$search%");
            });
        }

        $lessors = $query->get()->map(function ($company) {
            // Входящие УПД (арендодатель → платформа) — сколько должны арендодателю
            $incomingUpds = Upd::where('lessor_company_id', $company->id)
                ->where('type', Upd::TYPE_INCOMING)
                ->whereIn('status', ['processed', 'accepted'])
                ->get();

            $totalAccrued = $incomingUpds->sum('total_amount');

            // Выплаты арендодателю
            $totalPaid = TransactionEntry::where('company_id', $company->id)
                ->where('purpose', TransactionEntry::PURPOSE_LESSOR_PAYOUT)
                ->active()
                ->sum('amount');

            // Ручные корректировки
            $adjustments = BalanceAdjustment::where('company_id', $company->id)->get();
            $totalAdjustments = $adjustments->sum('signed_amount');

            $debt = $totalAccrued - $totalPaid + $totalAdjustments;

            // Просрочка
            $overdueUpds = $incomingUpds->filter(function ($upd) {
                $paid = TransactionEntry::where('company_id', $upd->lessor_company_id)
                    ->where('purpose', TransactionEntry::PURPOSE_LESSOR_PAYOUT)
                    ->where('source_type', Upd::class)
                    ->where('source_id', $upd->id)
                    ->active()
                    ->sum('amount');
                return $paid < $upd->total_amount && $upd->issue_date < now()->subDays(15);
            });

            return (object) [
                'id' => $company->id,
                'legal_name' => $company->legal_name,
                'inn' => $company->inn,
                'phone' => $company->phone,
                'total_accrued' => $totalAccrued,
                'total_paid' => $totalPaid,
                'total_adjustments' => $totalAdjustments,
                'total_debt' => max(0, $debt),
                'overdue_debt' => $overdueUpds->sum('total_amount'),
                'company' => $company,
            ];
        })->sortByDesc('total_debt');

        $totals = [
            'total_debt' => $lessors->sum('total_debt'),
            'total_accrued' => $lessors->sum('total_accrued'),
            'overdue_debt' => $lessors->sum('overdue_debt'),
        ];

        return view('admin.finance.lessor-debts', compact('lessors', 'totals'));
    }

    /**
     * Форма ручной корректировки баланса
     */
    public function adjustmentCreate()
    {
        $companies = Company::where(function ($q) {
            $q->where('is_lessee', true)->orWhere('is_lessor', true);
        })->orderBy('legal_name')->get();

        return view('admin.finance.adjustment-create', compact('companies'));
    }

    /**
     * Сохранение ручной корректировки
     */
    public function adjustmentStore(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'required|string|max:500',
        ]);

        BalanceAdjustment::create([
            'company_id' => $validated['company_id'],
            'admin_id' => auth()->id(),
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('admin.finance.balance-adjustments')
            ->with('success', 'Корректировка баланса выполнена!');
    }

    /**
     * История всех корректировок
     */
    public function balanceAdjustments(Request $request)
    {
        $query = BalanceAdjustment::with(['company', 'admin']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $adjustments = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $companies = Company::where(function ($q) {
            $q->where('is_lessee', true)->orWhere('is_lessor', true);
        })->orderBy('legal_name')->get();

        return view('admin.finance.balance-adjustments', compact('adjustments', 'companies'));
    }

    /**
     * Акт сверки по компании (детализация по УПД)
     */
    public function reconciliationAct(Company $company)
    {
        // Все УПД где компания — арендатор или арендодатель
        $upds = Upd::where(function ($q) use ($company) {
                $q->where('lessee_company_id', $company->id)
                  ->orWhere('lessor_company_id', $company->id);
            })
            ->whereIn('status', ['processed', 'accepted'])
            ->orderBy('issue_date')
            ->orderBy('created_at')
            ->get();

        $entries = [];
        $runningBalance = 0;

        foreach ($upds as $upd) {
            $isLessee = $upd->lessee_company_id === $company->id;
            $accrual = $isLessee ? $upd->total_amount : 0;
            $payout = !$isLessee ? $upd->total_amount : 0;

            // Платежи, привязанные к этому УПД
            $payments = TransactionEntry::where('company_id', $company->id)
                ->where('source_type', Upd::class)
                ->where('source_id', $upd->id)
                ->active()
                ->get();

            $paymentSum = $payments->sum('amount');

            $runningBalance += ($accrual - $payout - $paymentSum);

            $entries[] = (object) [
                'date' => $upd->issue_date,
                'number' => $upd->number,
                'description' => ($isLessee ? 'УПД (начисление)' : 'УПД (поставка)')
                    . " — {$upd->order_id}",
                'accrual' => $accrual > 0 ? $accrual : ($payout > 0 ? $payout : 0),
                'type' => $isLessee ? 'accrual' : 'payout',
                'payment' => $paymentSum,
                'balance' => $runningBalance,
            ];
        }

        // Добавляем корректировки
        $adjustments = BalanceAdjustment::where('company_id', $company->id)
            ->orderBy('created_at')
            ->get();

        foreach ($adjustments as $adj) {
            $runningBalance += $adj->signed_amount;
            $entries[] = (object) [
                'date' => $adj->created_at,
                'number' => 'Корр. #'.$adj->id,
                'description' => $adj->comment,
                'accrual' => $adj->type === 'credit' ? $adj->amount : 0,
                'type' => 'adjustment',
                'payment' => $adj->type === 'debit' ? $adj->amount : 0,
                'balance' => $runningBalance,
            ];
        }

        $finalBalance = $runningBalance;

        return view('admin.finance.reconciliation-act', compact('company', 'entries', 'finalBalance'));
    }

    /**
     * Детализация по конкретной компании (заказы)
     */
    public function companyDetail(Company $company)
    {
        $upds = Upd::where(function ($q) use ($company) {
                $q->where('lessee_company_id', $company->id)
                  ->orWhere('lessor_company_id', $company->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $adjustments = BalanceAdjustment::where('company_id', $company->id)
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAdjustments = $adjustments->sum('signed_amount');

        return view('admin.finance.company-detail', compact('company', 'upds', 'adjustments', 'totalAdjustments'));
    }
}
