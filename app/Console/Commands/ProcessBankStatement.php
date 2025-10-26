<?php

namespace App\Console\Commands;

use App\Models\BankStatement;
use App\Services\Parsers\BankStatementParser;
use App\Services\PaymentProcessingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBankStatement extends Command
{
    protected $signature = 'bank-statement:process {id : ID банковской выписки}';

    protected $description = 'Process bank statement from database';

    protected $paymentProcessingService;

    public function __construct(PaymentProcessingService $paymentProcessingService)
    {
        parent::__construct();
        $this->paymentProcessingService = $paymentProcessingService;
    }

    public function handle(): void
    {
        $statement = BankStatement::findOrFail($this->argument('id'));

        if ($statement->status !== 'pending') {
            $this->error('Выписка уже была обработана или находится в обработке.');

            return;
        }

        $statement->markAsProcessing();

        if (! Storage::exists($statement->filename)) {
            $statement->markAsFailed("Файл не найден: {$statement->filename}");
            $this->error("File not found: {$statement->filename}");

            return;
        }

        try {
            $content = Storage::get($statement->filename);
            $parser = new BankStatementParser;
            $transactions = $parser->parse($content);

            $processed = 0;
            $errors = 0;
            $errorLog = '';

            foreach ($transactions as $transaction) {
                try {
                    $this->paymentProcessingService->processPayment(
                        $transaction['payer_inn'],
                        $transaction['amount'],
                        $transaction['date'],
                        $transaction['purpose'],
                        $transaction['idempotency_key']
                    );

                    $processed++;
                    $this->info("Processed payment: {$transaction['amount']} from {$transaction['payer_inn']}");
                } catch (\Exception $e) {
                    $errors++;
                    $errorMessage = "Error processing payment: {$e->getMessage()}";
                    $errorLog .= $errorMessage."\n";
                    $this->error($errorMessage);
                    Log::error('Payment processing error', [
                        'transaction' => $transaction,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $statement->markAsCompleted($processed, $errors, $errorLog);
            $this->info("Bank statement processing completed. Processed: {$processed}, Errors: {$errors}");

        } catch (\Exception $e) {
            $errorMessage = "Error processing bank statement: {$e->getMessage()}";
            $statement->markAsFailed($errorMessage);
            $this->error($errorMessage);
            Log::error('Bank statement processing failed', [
                'statement_id' => $statement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
