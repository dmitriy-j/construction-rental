<?php

namespace App\Console\Commands;

use App\Models\BankStatement;
use App\Models\BankStatementTransaction;
use App\Models\TransactionEntry;
use Illuminate\Console\Command;

class FixBankStatementTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-bank-statement-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $statement = BankStatement::find(2);
        $transactionEntries = TransactionEntry::where('description', 'like', '%Оплата по выписке%')
            ->where('created_at', '>=', $statement->created_at)
            ->get();

        foreach ($transactionEntries as $entry) {
            // Создаем запись в bank_statement_transactions
            BankStatementTransaction::create([
                'bank_statement_id' => $statement->id,
                'date' => $entry->created_at,
                'amount' => $entry->amount,
                'type' => $entry->type === 'debit' ? 'incoming' : 'outgoing',
                'payer_name' => $entry->company->legal_name,
                'payer_inn' => $entry->company->inn,
                'payee_name' => 'Платформа',
                'payee_inn' => config('platform.inn', '9723125209'),
                'purpose' => $entry->description,
                'status' => 'processed',
                'source_type' => get_class($entry),
                'source_id' => $entry->id,
                'company_id' => $entry->company_id,
                'idempotency_key' => $entry->idempotency_key,
            ]);
        }

        $statement->refreshStatus();
        $this->info('Восстановлено транзакций: '.$transactionEntries->count());
    }
}
