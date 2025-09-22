<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\BalanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateLateFees extends Command
{
    protected $signature = 'finance:calculate-late-fees';

    protected $description = 'Calculate late fees for overdue invoices';

    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    public function handle()
    {
        $overdueInvoices = Invoice::where('status', Invoice::STATUS_OVERDUE)
            ->where('due_date', '<', now()->subDays(1))
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = now()->diffInDays(Carbon::parse($invoice->due_date));
            $lateFee = $invoice->amount * 0.01 * $daysOverdue; // 1% в день

            // Начисляем пеню
            $this->balanceService->commitTransaction(
                $invoice->company,
                $lateFee,
                'credit',
                'late_fee',
                $invoice,
                "Пеня за просрочку платежа по счету №{$invoice->number}",
                'late_fee_'.$invoice->id.'_'.now()->format('Ymd')
            );

            $this->info("Начислена пеня {$lateFee} ₽ для счета №{$invoice->number}");
        }

        $this->info('Начисление пеней завершено.');
    }
}
