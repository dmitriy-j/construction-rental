<?php

namespace App\Jobs;

use App\Models\BankStatement;
use App\Services\BankStatementProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBankStatementTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionData;

    protected $bankStatementId;

    public function __construct(array $transactionData, int $bankStatementId)
    {
        $this->transactionData = $transactionData;
        $this->bankStatementId = $bankStatementId;
    }

    public function handle(BankStatementProcessingService $processingService)
    {
        $processingService->processTransaction($this->transactionData, $this->bankStatementId);

        // Обновляем статус выписки после обработки каждой транзакции
        $statement = BankStatement::find($this->bankStatementId);
        if ($statement) {
            $this->updateStatementStatus($statement);
        }
    }

    protected function updateStatementStatus(BankStatement $statement): void
    {
        $processedCount = $statement->transactions()
            ->where('status', 'processed')
            ->count();

        $errorCount = $statement->transactions()
            ->where('status', 'error')
            ->count();

        $totalProcessed = $processedCount + $errorCount;

        if ($totalProcessed === 0) {
            $status = 'processing';
        } elseif ($errorCount > 0) {
            $status = 'completed_with_errors';
        } else {
            $status = 'completed';
        }

        $statement->update([
            'processed_count' => $processedCount,
            'error_count' => $errorCount,
            'status' => $status,
            'processed_at' => $status !== 'processing' ? now() : null,
        ]);
    }
}
