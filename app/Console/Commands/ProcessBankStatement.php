<?php

namespace App\Console\Commands;

use App\Models\BankStatement;
use App\Services\BankStatementProcessingService;
use App\Services\Parsers\BankStatementParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBankStatement extends Command
{
    protected $signature = 'bank-statement:process {id : ID банковской выписки}';
    protected $description = 'Process bank statement from database';

    protected $processingService;

    public function __construct(BankStatementProcessingService $processingService)
    {
        parent::__construct();
        $this->processingService = $processingService;
    }

    public function handle(): void
    {
        $statement = BankStatement::findOrFail($this->argument('id'));

        if ($statement->status !== 'pending') {
            $this->error('Выписка уже была обработана или находится в обработке.');
            return;
        }

        $statement->markAsProcessing();

        if (!Storage::exists($statement->filename)) {
            $statement->markAsFailed("Файл не найден: {$statement->filename}");
            $this->error("File not found: {$statement->filename}");
            return;
        }

        try {
            $content = Storage::get($statement->filename);
            $parser = new BankStatementParser;
            $transactions = $parser->parse($content);

            foreach ($transactions as $transaction) {
                try {
                    // ✅ ИСПОЛЬЗУЕМ ПРАВИЛЬНЫЙ СЕРВИС
                    $this->processingService->processTransaction($transaction, $statement->id);
                    $this->info("Processed transaction: {$transaction['Сумма']}");
                } catch (\Exception $e) {
                    $this->error("Error processing transaction: {$e->getMessage()}");
                    Log::error('Transaction processing error', [
                        'transaction' => $transaction,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Обновляем статус выписки
            $statement->refreshStatus();
            $this->info("Bank statement processing completed. Status: {$statement->status}");

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
