<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\Log;

class BankStatementParser
{
    public function parse(string $content): array
    {
        // Конвертируем из Windows-1251 в UTF-8
        $content = $this->convertWindows1251ToUtf8($content);

        Log::debug('BankStatementParser: начат парсинг файла после конвертации', [
            'content_length' => strlen($content),
            'first_500_chars' => substr($content, 0, 500)
        ]);

        if ($this->is1CFormat($content)) {
            Log::debug('BankStatementParser: определен формат 1C');
            return $this->parse1CTextFormat($content);
        } else {
            Log::debug('BankStatementParser: определен текстовый формат');
            return $this->parseTextFormat($content);
        }
    }

    protected function convertWindows1251ToUtf8(string $content): string
    {
        // Прямая конвертация из Windows-1251 в UTF-8
        $converted = iconv('Windows-1251', 'UTF-8//IGNORE', $content);

        if ($converted === false) {
            Log::warning('Не удалось конвертировать из Windows-1251 в UTF-8, используем оригинал');
            return $content;
        }

        Log::debug('Успешно сконвертировано из Windows-1251 в UTF-8', [
            'original_size' => strlen($content),
            'converted_size' => strlen($converted)
        ]);

        return $converted;
    }

    protected function is1CFormat(string $content): bool
    {
        return strpos($content, '1CClientBankExchange') !== false ||
               strpos($content, 'СекцияДокумент') !== false;
    }

    protected function parse1CTextFormat(string $content): array
    {
        $transactions = [];
        $lines = explode("\n", trim($content));
        $currentTransaction = [];
        $inTransaction = false;
        $documentCount = 0;

        Log::debug('1C Parser: начат разбор после конвертации', ['lines_count' => count($lines)]);

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            // Логируем ключевые строки для отладки
            if (str_contains($line, 'СекцияДокумент') || str_contains($line, 'КонецДокумента')) {
                Log::debug("1C Parser Key Line {$lineNumber}", ['line' => $line]);
            }

            // Ищем начало ЛЮБОЙ секции документа
            if (str_starts_with($line, 'СекцияДокумент=')) {
                $documentCount++;
                Log::debug("1C Parser: найден начало документа #{$documentCount} на строке {$lineNumber}", ['line' => $line]);
                $inTransaction = true;
                $currentTransaction = [];
                $currentTransaction['ТипДокумента'] = str_replace('СекцияДокумент=', '', $line);
                continue;
            }

            if (str_starts_with($line, 'КонецДокумента')) {
                if ($inTransaction) {
                    Log::debug("1C Parser: найден конец документа #{$documentCount} на строке {$lineNumber}");
                    $inTransaction = false;

                    // Обработка и валидация транзакции
                    if ($this->isValidTransaction($currentTransaction)) {
                        // Нормализуем данные
                        $normalizedTransaction = $this->normalizeTransactionData($currentTransaction);
                        $transactions[] = $normalizedTransaction;

                        Log::debug('1C Parser: добавлена транзакция', [
                            'document_number' => $documentCount,
                            'type' => $normalizedTransaction['ТипДокумента'] ?? 'unknown',
                            'amount' => $normalizedTransaction['Сумма'],
                            'payer_inn' => $normalizedTransaction['ПлательщикИНН'] ?? '',
                            'payee_inn' => $normalizedTransaction['ПолучательИНН'] ?? ''
                        ]);
                    } else {
                        Log::warning('1C Parser: пропущена невалидная транзакция', [
                            'document_number' => $documentCount,
                            'transaction' => $currentTransaction
                        ]);
                    }
                }
                continue;
            }

            if ($inTransaction && str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $currentTransaction[trim($key)] = trim($value);
            }
        }

        Log::info('1C Parser: итоговый результат', [
            'total_documents' => $documentCount,
            'valid_transactions' => count($transactions)
        ]);

        return $transactions;
    }

    protected function isValidTransaction(array $transaction): bool
    {
        // Минимальные требования для валидной транзакции
        $hasAmount = !empty($transaction['Сумма']) && $this->validateAndParseAmount($transaction['Сумма']) > 0;
        $hasDate = !empty($transaction['Дата']);
        $hasParties = (!empty($transaction['ПлательщикИНН']) && $transaction['ПлательщикИНН'] !== '0000000000') ||
                     (!empty($transaction['ПолучательИНН']) && $transaction['ПолучательИНН'] !== '0000000000');

        $isValid = $hasAmount && $hasDate && $hasParties;

        if (!$isValid) {
            Log::debug('Транзакция невалидна', [
                'hasAmount' => $hasAmount,
                'hasDate' => $hasDate,
                'hasParties' => $hasParties,
                'transaction_keys' => array_keys($transaction)
            ]);
        }

        return $isValid;
    }

    protected function normalizeTransactionData(array $transaction): array
    {
        $normalized = [];

        // Маппинг полей 1C в наши поля
        $fieldMapping = [
            'Номер' => 'Номер',
            'Дата' => 'Дата',
            'Сумма' => 'Сумма',
            'ПлательщикСчет' => 'ПлательщикСчет',
            'ПлательщикИНН' => 'ПлательщикИНН',
            'Плательщик1' => 'Плательщик1',
            'ПлательщикРасчСчет' => 'ПлательщикСчет',
            'ПлательщикБИК' => 'ПлательщикБИК',
            'ПолучательСчет' => 'ПолучательСчет',
            'ПолучательИНН' => 'ПолучательИНН',
            'Получатель1' => 'Получатель1',
            'ПолучательРасчСчет' => 'ПолучательСчет',
            'ПолучательБИК' => 'ПолучательБИК',
            'НазначениеПлатежа' => 'НазначениеПлатежа',
            'Назначение' => 'НазначениеПлатежа',
            'ТипДокумента' => 'ТипДокумента'
        ];

        foreach ($fieldMapping as $sourceField => $targetField) {
            if (isset($transaction[$sourceField])) {
                $normalized[$targetField] = $transaction[$sourceField];
            }
        }

        // Обработка суммы
        if (isset($normalized['Сумма'])) {
            $normalized['Сумма'] = $this->validateAndParseAmount($normalized['Сумма']);
        }

        // Обработка даты
        if (isset($normalized['Дата'])) {
            try {
                // Сохраняем оригинальный формат даты
                $normalized['Дата'] = \Carbon\Carbon::createFromFormat('d.m.Y', $normalized['Дата'])->format('d.m.Y');
            } catch (\Exception $e) {
                Log::warning('Неверный формат даты в транзакции', [
                    'date' => $normalized['Дата'],
                    'transaction' => $normalized
                ]);
                $normalized['Дата'] = now()->format('d.m.Y');
            }
        }

        // Очистка ИНН
        if (isset($normalized['ПлательщикИНН'])) {
            $normalized['ПлательщикИНН'] = $this->cleanInn($normalized['ПлательщикИНН']);
        }
        if (isset($normalized['ПолучательИНН'])) {
            $normalized['ПолучательИНН'] = $this->cleanInn($normalized['ПолучательИНН']);
        }

        // Генерация idempotency key
        $normalized['idempotency_key'] = $this->generateIdempotencyKey($normalized);

        return $normalized;
    }

    protected function validateAndParseAmount($amount): float
    {
        if (is_string($amount)) {
            // Удаляем все пробелы (тысячные разделители) и заменяем запятые на точки
            $amount = str_replace([' ', ','], ['', '.'], trim($amount));
        }

        if (!is_numeric($amount)) {
            Log::warning('Некорректный формат суммы', ['amount' => $amount]);
            return 0.0;
        }

        $parsedAmount = (float) $amount;

        if ($parsedAmount <= 0) {
            Log::warning('Сумма должна быть положительной', ['amount' => $amount]);
            return 0.0;
        }

        return $parsedAmount;
    }

    protected function cleanInn(?string $inn): string
    {
        if (empty($inn)) {
            return '0000000000';
        }

        $cleaned = preg_replace('/[^0-9]/', '', $inn);
        return !empty($cleaned) ? $cleaned : '0000000000';
    }

    protected function generateIdempotencyKey(array $transaction): string
    {
        $uniqueSourceString = implode('|', [
            $transaction['Номер'] ?? '',
            $transaction['Дата'] ?? '',
            $transaction['Сумма'] ?? '',
            $transaction['ПлательщикИНН'] ?? '',
            $transaction['ПолучательИНН'] ?? '',
            $transaction['НазначениеПлатежа'] ?? '',
        ]);

        return 'bank_stmt_'.md5($uniqueSourceString);
    }

    protected function parseTextFormat(string $content): array
    {
        $transactions = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $parts = preg_split('/\s{2,}/', trim($line));

            if (count($parts) >= 5) {
                $transaction = [
                    'date' => $parts[0],
                    'amount' => (float) preg_replace('/[^0-9.]/', '', $parts[3]),
                    'payer_name' => $parts[1],
                    'payer_inn' => $this->extractInn($parts[2]),
                    'purpose' => $parts[4],
                    'idempotency_key' => 'bank_'.md5($parts[0].$parts[3].$parts[4]),
                ];

                $transactions[] = $transaction;
            }
        }

        Log::info('Text Parser: распознано транзакций', ['count' => count($transactions)]);

        return $transactions;
    }

    protected function extractInn(string $text): string
    {
        preg_match('/\b\d{10,12}\b/', $text, $matches);
        return $matches[0] ?? '';
    }
}
