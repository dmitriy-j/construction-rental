<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Services\BankStatementProcessingService;
use App\Services\Parsers\BankStatementParser;
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

    public function handle(BankStatementProcessingService $processingService)
    {
        $this->statement->update(['status' => 'processing']);

        try {
            $content = Storage::get($this->statement->filename);
            $parser = new BankStatementParser;
            $transactions = $parser->parse($content);

            // ✅ ИСПОЛЬЗУЕМ ПРАВИЛЬНЫЙ СЕРВИС
            foreach ($transactions as $transaction) {
                $processingService->processTransaction($transaction, $this->statement->id);
            }

            // Обновляем статус выписки
            $this->statement->refreshStatus();

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
