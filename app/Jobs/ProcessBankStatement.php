<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Services\Parsers\BankStatementParser;
use App\Services\PaymentProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBankStatement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $statement;

    public function __construct(BankStatement $statement)
    {
        $this->statement = $statement;
    }

    public function handle(PaymentProcessingService $paymentProcessingService)
    {
        $this->statement->update(['status' => 'processing']);

        try {
            $content = Storage::get($this->statement->filename);
            $parser = new BankStatementParser;
            $transactions = $parser->parse($content);

            $processed = 0;
            $errors = 0;
            $errorLog = '';

            foreach ($transactions as $transaction) {
                try {
                    $paymentProcessingService->processPayment(
                        $transaction['payer_inn'],
                        $transaction['amount'],
                        $transaction['date'],
                        $transaction['purpose'],
                        $transaction['idempotency_key']
                    );
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    $errorLog .= "Error processing payment: {$e->getMessage()}\n";
                    Log::error('Payment processing error', [
                        'transaction' => $transaction,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->statement->update([
                'status' => 'completed',
                'processed_count' => $processed,
                'error_count' => $errors,
                'error_log' => $errorLog,
                'transactions_count' => count($transactions),
            ]);

        } catch (\Exception $e) {
            $this->statement->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);
            Log::error('Bank statement processing failed', [
                'statement_id' => $this->statement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
