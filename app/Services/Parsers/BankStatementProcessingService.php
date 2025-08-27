<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\Log;

class BankStatementParser
{
    public function parse(string $content): array
    {
        // Определяем формат выписки
        if ($this->is1CFormat($content)) {
            return $this->parse1CFormat($content);
        } else {
            return $this->parseTextFormat($content);
        }
    }

    protected function is1CFormat(string $content): bool
    {
        return strpos($content, '1CClientBankExchange') !== false;
    }

    protected function parse1CFormat(string $content): array
    {
        $transactions = [];

        try {
            $xml = simplexml_load_string($content);

            foreach ($xml->Документ as $doc) {
                $transaction = [
                    'date' => (string) $doc['Дата'],
                    'amount' => (float) $doc['Сумма'],
                    'payer_name' => (string) $doc['Плательщик'],
                    'payer_inn' => (string) $doc['ИННПлательщика'],
                    'payer_account' => (string) $doc['СчетПлательщика'],
                    'recipient_name' => (string) $doc['Получатель'],
                    'recipient_inn' => (string) $doc['ИННПолучателя'],
                    'recipient_account' => (string) $doc['СчетПолучателя'],
                    'purpose' => (string) $doc['НазначениеПлатежа'],
                    'idempotency_key' => 'bank_' . md5((string) $doc['Дата'] . (string) $doc['Сумма'] . (string) $doc['НазначениеПлатежа'])
                ];

                $transactions[] = $transaction;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка парсинга выписки 1C', ['error' => $e->getMessage()]);
            throw new \Exception("Не удалось распарсить выписку 1C: " . $e->getMessage());
        }

        return $transactions;
    }

    protected function parseTextFormat(string $content): array
    {
        $transactions = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            // Парсим стандартный текстовый формат выписки
            // Формат может отличаться в зависимости от банка
            $parts = preg_split('/\s{2,}/', trim($line));

            if (count($parts) >= 5) {
                $transaction = [
                    'date' => $parts[0],
                    'amount' => (float) preg_replace('/[^0-9.]/', '', $parts[3]),
                    'payer_name' => $parts[1],
                    'payer_inn' => $this->extractInn($parts[2]),
                    'purpose' => $parts[4],
                    'idempotency_key' => 'bank_' . md5($parts[0] . $parts[3] . $parts[4])
                ];

                $transactions[] = $transaction;
            }
        }

        return $transactions;
    }

    protected function extractInn(string $text): string
    {
        // Извлекаем ИНН из текста (10 или 12 цифр)
        preg_match('/\b\d{10,12}\b/', $text, $matches);
        return $matches[0] ?? '';
    }
}
