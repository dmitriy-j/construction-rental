<?php

namespace App\Services\Parsers;

class BankStatementParser
{
    public function parse(string $content): array
    {
        if ($this->is1CFormat($content)) {
            return $this->parse1CTextFormat($content);
        } else {
            return $this->parseTextFormat($content);
        }
    }

    protected function is1CFormat(string $content): bool
    {
        return strpos($content, '1CClientBankExchange') !== false;
    }

    protected function parse1CTextFormat(string $content): array
    {
        $transactions = [];
        $lines = explode("\n", trim($content));
        $currentTransaction = [];
        $inTransaction = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'СекцияДокумент=Платежное поручение')) {
                $inTransaction = true;
                $currentTransaction = [];

                continue;
            }

            if (str_starts_with($line, 'КонецДокумента')) {
                $inTransaction = false;

                // Обработка суммы
                if (isset($currentTransaction['Сумма'])) {
                    try {
                        $currentTransaction['Сумма'] = is_numeric($currentTransaction['Сумма'])
                            ? (float) $currentTransaction['Сумма']
                            : 0;
                    } catch (\Exception $e) {
                        \Log::warning('Некорректная сумма в транзакции', [
                            'сумма' => $currentTransaction['Сумма'],
                            'транзакция' => $currentTransaction,
                        ]);
                        $currentTransaction['Сумма'] = 0;
                    }
                }

                if (! empty($currentTransaction)) {
                    $transactions[] = $currentTransaction;
                }

                continue;
            }

            if ($inTransaction && str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $currentTransaction[$key] = trim($value);
            }
        }

        \Log::info('Распознано транзакций', ['count' => count($transactions)]);

        return $transactions;
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

        return $transactions;
    }

    protected function extractInn(string $text): string
    {
        preg_match('/\b\d{10,12}\b/', $text, $matches);

        return $matches[0] ?? '';
    }
}
