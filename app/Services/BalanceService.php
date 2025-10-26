<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BalanceService
{
    /**
     * Провести транзакцию и обновить баланс компании
     *
     * @param  string  $type  'debit' | 'credit'
     * @param  mixed  $source
     *
     * @throws \Exception
     */
    public function commitTransaction(
        Company $company,
        float $amount,
        string $type,
        string $purpose,
        $source = null,
        ?string $description = null,
        ?string $idempotencyKey = null
    ): TransactionEntry {
        // Валидация
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Сумма транзакции должна быть положительной.');
        }

        if (! in_array($type, ['debit', 'credit'])) {
            throw new \InvalidArgumentException("Тип транзакции должен быть 'debit' или 'credit'.");
        }

        // Генерация ключа идемпотентности, если не предоставлен
        $idempotencyKey = $idempotencyKey ?: $this->generateIdempotencyKey($company->id, $type, $purpose, $amount, $source);

        // Проверка на дубликат
        if ($existingEntry = TransactionEntry::where('idempotency_key', $idempotencyKey)->first()) {
            Log::warning('Попытка создать дублирующую транзакцию', ['idempotency_key' => $idempotencyKey]);

            return $existingEntry;
        }

        try {
            DB::beginTransaction();

            // Рассчитать новый баланс
            $currentBalance = $this->getCurrentBalance($company);
            $newBalance = $type === 'debit'
                ? $currentBalance + $amount
                : $currentBalance - $amount;

            // Создать проводку
            $entry = TransactionEntry::create([
                'company_id' => $company->id,
                'amount' => $amount,
                'type' => $type,
                'purpose' => $purpose,
                'balance_snapshot' => $newBalance,
                'description' => $description,
                'source_type' => $source ? get_class($source) : null,
                'source_id' => $source ? $source->id : null,
                'idempotency_key' => $idempotencyKey,
            ]);

            DB::commit();

            Log::info('Транзакция успешно проведена', [
                'company_id' => $company->id,
                'transaction_id' => $entry->id,
                'type' => $type,
                'purpose' => $purpose,
                'amount' => $amount,
                'old_balance' => $currentBalance,
                'new_balance' => $newBalance,
            ]);

            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка проведения транзакции', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Не удалось провести транзакцию: '.$e->getMessage());
        }
    }

    /**
     * Получить текущий баланс компании
     */
    public function getCurrentBalance(Company $company): float
    {
        // Сумма последних неподтвержденных проводок по компании
        $lastEntry = TransactionEntry::where('company_id', $company->id)
            ->active()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastEntry ? (float) $lastEntry->balance_snapshot : 0.0;
    }

    /**
     * Получить историю транзакций с пагинацией
     */
    public function getTransactionHistory(Company $company, int $perPage = 20)
    {
        return TransactionEntry::where('company_id', $company->id)
            ->with(['source'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Генерация уникального ключа идемпотентности
     */
    protected function generateIdempotencyKey($companyId, $type, $purpose, $amount, $source): string
    {
        $sourceType = $source ? get_class($source) : 'null';
        $sourceId = $source ? $source->id : 'null';

        $baseString = "{$companyId}|{$type}|{$purpose}|{$amount}|{$sourceType}|{$sourceId}|".now()->toISOString();

        return 'trans_'.Str::substr(md5($baseString), 0, 32);
    }
}
