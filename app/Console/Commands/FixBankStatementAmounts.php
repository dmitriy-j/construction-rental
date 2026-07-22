<?php

namespace App\Console\Commands;

use App\Models\BankStatement;
use App\Models\BankStatementTransaction;
use App\Services\BankStatementProcessingService;
use App\Services\Parsers\BankStatementParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FixBankStatementAmounts extends Command
{
    protected $signature = 'bank-statements:fix-zero-amounts
                            {--dry-run : Только показать, что будет исправлено, без изменений}
                            {--statement-id= : Исправить транзакции для конкретной выписки по ID}
                            {--file= : Путь к исходному файлу выписки для перепарсинга (если файл сохранён)}';

    protected $description = 'Исправляет amount=0 у ошибочных транзакций. Без --file только диагностика.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $statementId = $this->option('statement-id');
        $filePath = $this->option('file');

        // Если указан файл — пытаемся перепарсить и исправить
        if ($filePath) {
            return $this->repairFromFile($filePath, $statementId, $dryRun);
        }

        // Иначе — только диагностика
        return $this->diagnose();
    }

    protected function repairFromFile(string $filePath, ?string $statementId, bool $dryRun): int
    {
        if (!$statementId) {
            $this->error('Для исправления из файла требуется --statement-id');
            return 1;
        }

        if (!file_exists($filePath)) {
            $this->error("Файл не найден: {$filePath}");
            return 1;
        }

        $statement = BankStatement::find($statementId);
        if (!$statement) {
            $this->error("Выписка #{$statementId} не найдена");
            return 1;
        }

        $this->info("Перепарсинг файла {$filePath} для выписки #{$statement->id} ({$statement->filename})");

        $content = file_get_contents($filePath);
        $parser = new BankStatementParser();
        $parsedTransactions = $parser->parse($content);

        $this->info("Найдено транзакций в файле: " . count($parsedTransactions));

        // Индексируем по idempotency_key для быстрого поиска
        $processedService = app(BankStatementProcessingService::class);
        $parsedIndex = [];
        foreach ($parsedTransactions as $txData) {
            $key = $processedService->generateIdempotencyKey($txData);
            $parsedIndex[$key] = $txData;
        }

        $errorTransactions = $statement->transactions()
            ->where('status', 'error')
            ->where('amount', 0)
            ->get();

        $this->line("Ошибочных транзакций с amount=0: {$errorTransactions->count()}");

        $fixedCount = 0;
        $notFoundCount = 0;

        foreach ($errorTransactions as $transaction) {
            // Ищем по idempotency_key (без префикса error_)
            $originalKey = str_replace('error_', '', $transaction->idempotency_key);

            if (isset($parsedIndex[$originalKey])) {
                $amount = $parsedIndex[$originalKey]['Сумма'];
                $parsedAmount = $processedService->parseAmountSafe($amount);

                if ($parsedAmount > 0) {
                    if (!$dryRun) {
                        $transaction->update(['amount' => $parsedAmount]);
                        $this->line("  [+] Транзакция #{$transaction->id}: сумма исправлена: 0 → {$parsedAmount}");
                    } else {
                        $this->line("  [~] Транзакция #{$transaction->id}: будет исправлена: 0 → {$parsedAmount}");
                    }
                    $fixedCount++;
                } else {
                    $this->warn("  [!] Транзакция #{$transaction->id}: не удалось распарсить сумму '{$amount}'");
                    $notFoundCount++;
                }
            } else {
                $this->warn("  [!] Транзакция #{$transaction->id}: не найдена в перепарсенном файле");
                $notFoundCount++;
            }
        }

        $this->newLine();
        $this->table(
            ['Показатель', 'Значение'],
            [
                ['Выписок с проблемами', 1],
                ['Транзакций исправлено', $fixedCount],
                ['Не найдено в файле', $notFoundCount],
            ]
        );

        if ($fixedCount > 0 && !$dryRun) {
            $statement->refreshStatus();
            $this->info("Статус выписки обновлён: {$statement->status}");
        }

        return 0;
    }

    protected function diagnose(): int
    {
        $statementId = $this->option('statement-id');

        $query = BankStatement::whereHas('transactions', function ($q) {
            $q->where('status', 'error')->where('amount', 0);
        });

        if ($statementId) {
            $query->where('id', $statementId);
        }

        $statements = $query->get();

        if ($statements->isEmpty()) {
            $this->info('Нет выписок с ошибочными транзакциями (amount = 0).');
            return 0;
        }

        $this->warn('Найдено выписок с проблемными транзакциями: ' . $statements->count());

        $totalSkipped = 0;

        foreach ($statements as $statement) {
            $this->newLine();
            $this->line("Выписка #{$statement->id}: {$statement->filename} (статус: {$statement->status})");

            $errorTransactions = $statement->transactions()
                ->where('status', 'error')
                ->where('amount', 0)
                ->get();

            $this->line("  Ошибочных транзакций с amount=0: {$errorTransactions->count()}");

            foreach ($errorTransactions as $transaction) {
                $this->line("    Транзакция #{$transaction->id} от {$transaction->date->format('d.m.Y')}, тип: {$transaction->type}");
                $this->line("      Плательщик: {$transaction->payer_name} (ИНН: {$transaction->payer_inn})");
                $this->line("      Получатель: {$transaction->payee_name} (ИНН: {$transaction->payee_inn})");
                $this->line("      Назначение: {$transaction->purpose}");
                $this->line("      Ошибка: {$transaction->error_message}");
                $totalSkipped++;
            }
        }

        $this->newLine();
        $this->table(
            ['Показатель', 'Значение'],
            [
                ['Выписок с проблемами', $statements->count()],
                ['Транзакций с amount=0', $totalSkipped],
            ]
        );

        $this->warn('Исходные файлы выписок не сохраняются на сервере автоматически.');
        $this->line('Варианты исправления:');
        $this->line('  1. Удалить выписки с ошибками и загрузить заново — новые транзакции будут с корректной суммой');
        $this->line('  2. Если исходный файл сохранён локально, выполнить:');
        $this->line('     php artisan bank-statements:fix-zero-amounts --statement-id={ID} --file={/путь/к/файлу.txt}');
        $this->line('');

        return 0;
    }
}
