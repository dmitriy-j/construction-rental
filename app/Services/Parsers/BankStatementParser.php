<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\Log;

class BankStatementParser
{
    protected array $errors = [];

    /**
     * Доступные форматы дат для распознавания
     */
    protected array $dateFormats = [
        'd.m.Y',
        'd/m/Y',
        'd.m.y',
        'Y-m-d',
        'Y/m/d',
    ];

    public function parse(string $content): array
    {
        $this->errors = [];

        // Конвертируем из Windows-1251 в UTF-8
        $content = $this->convertToUtf8($content);

        Log::debug('BankStatementParser: начат парсинг', [
            'length' => strlen($content),
            'preview' => mb_substr($content, 0, 300),
        ]);

        if ($this->is1CFormat($content)) {
            Log::debug('BankStatementParser: формат 1C');
            return $this->parse1CFormat($content);
        }

        // Пробуем автоопределение разделителя
        $delimiter = $this->detectDelimiter($content);
        Log::debug('BankStatementParser: определён разделитель', ['delimiter' => $delimiter === "\t" ? 'TAB' : ($delimiter ?: 'не определён')]);

        if ($delimiter) {
            return $this->parseDelimitedFormat($content, $delimiter);
        }

        // Fallback: старый парсер
        Log::warning('BankStatementParser: формат не определён, попытка text fallback');
        return $this->parseTextFormat($content);
    }

    /**
     * Получить ошибки парсинга
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    // ---- Кодировка ----

    protected function convertToUtf8(string $content): string
    {
        // Пробуем iconv
        $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $content);
        if ($converted !== false && $converted !== $content) {
            return $converted;
        }

        // Пробуем mb_convert_encoding
        if (function_exists('mb_convert_encoding')) {
            $detected = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'KOI8-R'], true);
            if ($detected && $detected !== 'UTF-8') {
                return mb_convert_encoding($content, 'UTF-8', $detected);
            }
        }

        return $content;
    }

    // ---- Определение формата ----

    protected function is1CFormat(string $content): bool
    {
        return str_contains($content, '1CClientBankExchange') ||
               str_contains($content, 'СекцияДокумент');
    }

    /**
     * Автоопределение разделителя в текстовом файле
     */
    protected function detectDelimiter(string $content): ?string
    {
        $lines = explode("\n", trim($content));
        $sampleLines = array_slice($lines, 0, 20);
        $delimiterScore = [];

        $candidates = ["\t", ';', ',', '|'];

        foreach ($candidates as $delim) {
            $score = 0;
            $consistent = 0;

            foreach ($sampleLines as $i => $line) {
                if (empty(trim($line))) continue;

                $count = substr_count($line, $delim);
                if ($count > 0) {
                    $score += $count;
                    // Если первая строка (заголовок) имеет столько же разделителей — бонус
                    if ($i === 0) {
                        $headerCount = $count;
                    }
                    if ($i > 0 && isset($headerCount) && $count === $headerCount) {
                        $consistent++;
                    }
                }
            }

            $delimiterScore[$delim] = [
                'score' => $score,
                'consistent' => $consistent,
            ];
        }

        // Выбираем разделитель с наибольшим количеством совпадений
        $best = null;
        $bestScore = 0;
        foreach ($delimiterScore as $delim => $data) {
            if ($data['score'] > $bestScore) {
                $bestScore = $data['score'];
                $best = $delim;
            }
        }

        // Минимум 2 вхождения для уверенности
        return ($bestScore >= 2) ? $best : null;
    }

    // ---- Парсер 1C ----

    protected function parse1CFormat(string $content): array
    {
        $transactions = [];
        // Извлекаем начальный остаток
        $openingBalance = $this->extractOpeningBalance($content);

        $lines = explode("\n", trim($content));
        $currentTx = [];
        $inTx = false;
        $docCount = 0;

        foreach ($lines as $num => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            try {
                if (str_starts_with($line, 'СекцияДокумент=')) {
                    $docCount++;
                    $inTx = true;
                    $currentTx = ['ТипДокумента' => substr($line, 16)];
                    $currentTx['_line_start'] = $num;
                    continue;
                }

                if (str_starts_with($line, 'КонецДокумента')) {
                    $inTx = false;
                    if ($this->isValidTransaction($currentTx)) {
                        $transactions[] = $this->normalizeTransactionData($currentTx);
                    }
                    continue;
                }

                if ($inTx && str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $currentTx[trim($key)] = trim($value);
                }
            } catch (\Exception $e) {
                $this->errors[] = "Строка {$num}: ошибка обработки — {$e->getMessage()}";
                Log::warning("1C Parser error at line {$num}", ['error' => $e->getMessage(), 'line' => $line]);
            }
        }

        Log::info('1C Parser завершён', [
            'документов' => $docCount,
            'транзакций' => count($transactions),
            'ошибок' => count($this->errors),
            'остаток' => $openingBalance,
        ]);

        return $transactions;
    }

    protected function extractOpeningBalance(string $content): ?float
    {
        if (preg_match('/НачальныйОстаток\s*=\s*([\d\.,]+)/', $content, $m)) {
            return (float) str_replace([' ', ','], ['', '.'], trim($m[1]));
        }
        return null;
    }

    protected function isValidTransaction(array $tx): bool
    {
        $amount = $this->parseAmount($tx['Сумма'] ?? '');
        $hasDate = !empty($tx['Дата']);
        $hasInn = ($tx['ПлательщикИНН'] ?? '') !== '0000000000'
               || ($tx['ПолучательИНН'] ?? '') !== '0000000000';
        return $amount > 0 && $hasDate && $hasInn;
    }

    // ---- Парсер с разделителем ----

    protected function parseDelimitedFormat(string $content, string $delimiter): array
    {
        $transactions = [];
        $lines = explode("\n", trim($content));
        $headerMap = $this->detectColumnHeaders($lines, $delimiter);
        $startLine = $headerMap ? 1 : 0;

        // Пропускаем шапку (строки до первой транзакции)
        $actualStart = $this->findFirstDataLine($lines, $delimiter, $startLine);

        for ($i = $actualStart; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            // Пропускаем итоговые строки
            if ($this->isSummaryLine($line)) continue;

            try {
                $parts = explode($delimiter, $line);
                $parts = array_map('trim', $parts);

                // Если есть заголовки — используем маппинг
                if ($headerMap) {
                    $tx = $this->mapColumns($parts, $headerMap);
                } else {
                    $tx = $this->parseByPosition($parts);
                }

                if ($tx && $this->isValidTransaction($tx)) {
                    $transactions[] = $this->normalizeTransactionData($tx);
                }
            } catch (\Exception $e) {
                $this->errors[] = "Строка {$i}: {$e->getMessage()}";
                Log::warning("Delimited parser error at line {$i}", ['error' => $e->getMessage()]);
            }
        }

        return $transactions;
    }

    /**
     * Определение заголовков колонок по первой строке
     */
    protected function detectColumnHeaders(array $lines, string $delimiter): ?array
    {
        if (empty($lines)) return null;

        $first = trim($lines[0]);
        $parts = explode($delimiter, $first);
        $parts = array_map('trim', $parts);

        // Типичные названия колонок для банковских выписок
        $knownHeaders = [
            'дата', 'date', 'сумма', 'sum', 'amount',
            'плательщик', 'payer', 'получатель', 'payee',
            'инн', 'inn', 'назначение', 'purpose', 'назначение платежа',
            'счет', 'account', 'бИК', 'bic', 'номер', 'number',
            'тип', 'type', 'вид', 'kind',
        ];

        $foundHeaders = [];
        foreach ($parts as $i => $header) {
            $headerLow = mb_strtolower(trim($header));
            foreach ($knownHeaders as $known) {
                if (mb_strpos($headerLow, $known) !== false) {
                    $foundHeaders[$i] = $headerLow;
                    break;
                }
            }
        }

        return !empty($foundHeaders) ? $foundHeaders : null;
    }

    /**
     * Поиск первой строки с данными (пропуск шапки)
     */
    protected function findFirstDataLine(array $lines, string $delimiter, int $startFrom): int
    {
        for ($i = $startFrom; $i < min(count($lines), 10); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $parts = explode($delimiter, $line);
            // Проверяем, что строка содержит дату и сумму
            if (count($parts) >= 3) {
                $hasDate = false;
                $hasNumber = false;
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (preg_match('/^\d{2}[\.\/]\d{2}[\.\/]\d{2,4}$/', $p)) $hasDate = true;
                    if (preg_match('/^[\d\s\.,]+$/', $p) && strlen($p) >= 4) $hasNumber = true;
                }
                if ($hasDate && $hasNumber) return $i;
            }
        }
        return $startFrom;
    }

    /**
     * Проверка на итоговую строку
     */
    protected function isSummaryLine(string $line): bool
    {
        $summaryKeywords = ['итого', 'всего', 'total', 'sum', 'остаток', 'balance',
                           'и т о г о', 'конец', 'end'];
        $lineLow = mb_strtolower($line);
        foreach ($summaryKeywords as $kw) {
            if (mb_strpos($lineLow, $kw) !== false) return true;
        }
        return false;
    }

    /**
     * Маппинг колонок по заголовкам
     */
    protected function mapColumns(array $parts, array $headerMap): array
    {
        $tx = [];
        foreach ($headerMap as $idx => $header) {
            $value = $parts[$idx] ?? '';

            if (str_contains($header, 'дата') || $header === 'date') {
                $tx['Дата'] = $value;
            } elseif (str_contains($header, 'сумм') || str_contains($header, 'amount') || $header === 'sum') {
                $tx['Сумма'] = $value;
            } elseif (str_contains($header, 'плательщик') || $header === 'payer') {
                if (str_contains($header, 'инн') || str_contains($header, 'inn')) {
                    $tx['ПлательщикИНН'] = $value;
                } elseif (str_contains($header, 'счет') || str_contains($header, 'account')) {
                    $tx['ПлательщикСчет'] = $value;
                } elseif (str_contains($header, 'бик') || $header === 'bic') {
                    $tx['ПлательщикБИК'] = $value;
                } else {
                    $tx['Плательщик1'] = $value;
                }
            } elseif (str_contains($header, 'получатель') || $header === 'payee') {
                if (str_contains($header, 'инн') || str_contains($header, 'inn')) {
                    $tx['ПолучательИНН'] = $value;
                } elseif (str_contains($header, 'счет') || str_contains($header, 'account')) {
                    $tx['ПолучательСчет'] = $value;
                } elseif (str_contains($header, 'бик') || $header === 'bic') {
                    $tx['ПолучательБИК'] = $value;
                } else {
                    $tx['Получатель1'] = $value;
                }
            } elseif (str_contains($header, 'назначен') || $header === 'purpose') {
                $tx['НазначениеПлатежа'] = $value;
            } elseif (str_contains($header, 'номер') || $header === 'number') {
                $tx['Номер'] = $value;
            } elseif (str_contains($header, 'тип') || $header === 'type' || $header === 'kind') {
                $tx['ТипДокумента'] = $value;
            }
        }
        return $tx;
    }

    /**
     * Парсинг по позициям (если нет заголовков)
     */
    protected function parseByPosition(array $parts): array
    {
        $tx = [];
        // Ожидается: Дата, Плательщик, Счёт/ИНН, Сумма, Назначение, ...
        foreach ($parts as $i => $p) {
            $p = trim($p);
            if (preg_match('/^\d{2}[\.\/]\d{2}[\.\/]\d{2,4}$/', $p) && empty($tx['Дата'])) {
                $tx['Дата'] = $p;
            } elseif (preg_match('/^[\d\s\.,]+$/', $p) && !isset($tx['Сумма']) && strlen($p) >= 4) {
                $tx['Сумма'] = $p;
            } elseif (preg_match('/^\d{10,12}$/', $p)) {
                if (!isset($tx['ПлательщикИНН'])) $tx['ПлательщикИНН'] = $p;
                else $tx['ПолучательИНН'] = $p;
            } elseif (!isset($tx['Плательщик1']) && mb_strlen($p) > 5) {
                $tx['Плательщик1'] = $p;
            } elseif (!isset($tx['НазначениеПлатежа']) && mb_strlen($p) > 10) {
                $tx['НазначениеПлатежа'] = $p;
            }
        }
        return $tx;
    }

    // ---- Старый текстовый парсер (fallback) ----

    protected function parseTextFormat(string $content): array
    {
        $transactions = [];
        $lines = explode("\n", $content);

        foreach ($lines as $num => $line) {
            if (empty(trim($line))) continue;
            if ($this->isSummaryLine($line)) continue;

            try {
                $parts = preg_split('/\s{2,}/', trim($line));
                if (count($parts) >= 5) {
                    $transactions[] = $this->normalizeTransactionData([
                        'Дата' => $parts[0],
                        'Сумма' => (float) preg_replace('/[^0-9.,]/', '', $parts[3]),
                        'Плательщик1' => $parts[1],
                        'ПлательщикИНН' => $this->extractInn($parts[2]),
                        'НазначениеПлатежа' => $parts[4],
                    ]);
                }
            } catch (\Exception $e) {
                $this->errors[] = "Строка {$num}: {$e->getMessage()}";
            }
        }

        return $transactions;
    }

    protected function extractInn(string $text): string
    {
        preg_match('/\b\d{10,12}\b/', $text, $m);
        return $m[0] ?? '';
    }

    // ---- Общие методы ----

    protected function parseAmount($amount): float
    {
        if (is_string($amount)) {
            $amount = str_replace([' ', ','], ['', '.'], trim($amount));
        }
        return is_numeric($amount) ? (float)$amount : 0.0;
    }

    protected function cleanInn(?string $inn): string
    {
        if (empty($inn)) return '0000000000';
        $c = preg_replace('/[^0-9]/', '', $inn);
        return $c ?: '0000000000';
    }

    protected function normalizeTransactionData(array $tx): array
    {
        $normalized = [];
        $fieldMapping = [
            'Номер' => 'Номер', 'Дата' => 'Дата', 'Сумма' => 'Сумма',
            'ПлательщикСчет' => 'ПлательщикСчет', 'ПлательщикИНН' => 'ПлательщикИНН',
            'Плательщик1' => 'Плательщик1', 'ПлательщикРасчСчет' => 'ПлательщикСчет',
            'ПлательщикБИК' => 'ПлательщикБИК',
            'ПолучательСчет' => 'ПолучательСчет', 'ПолучательИНН' => 'ПолучательИНН',
            'Получатель1' => 'Получатель1', 'ПолучательРасчСчет' => 'ПолучательСчет',
            'ПолучательБИК' => 'ПолучательБИК',
            'НазначениеПлатежа' => 'НазначениеПлатежа', 'Назначение' => 'НазначениеПлатежа',
            'ТипДокумента' => 'ТипДокумента',
        ];

        foreach ($fieldMapping as $src => $dst) {
            if (isset($tx[$src])) $normalized[$dst] = $tx[$src];
        }

        // Сумма
        if (isset($normalized['Сумма'])) {
            $normalized['Сумма'] = $this->parseAmount($normalized['Сумма']);
        }

        // Дата — пробуем разные форматы
        if (isset($normalized['Дата'])) {
            $date = null;
            foreach ($this->dateFormats as $fmt) {
                try {
                    $date = \Carbon\Carbon::createFromFormat($fmt, $normalized['Дата']);
                    if ($date) break;
                } catch (\Exception $e) {}
            }
            $normalized['Дата'] = $date ? $date->format('d.m.Y') : now()->format('d.m.Y');
        }

        // ИНН
        if (isset($normalized['ПлательщикИНН'])) $normalized['ПлательщикИНН'] = $this->cleanInn($normalized['ПлательщикИНН']);
        if (isset($normalized['ПолучательИНН'])) $normalized['ПолучательИНН'] = $this->cleanInn($normalized['ПолучательИНН']);

        // Idempotency key
        $normalized['idempotency_key'] = $this->generateIdempotencyKey($normalized);

        return $normalized;
    }

    protected function generateIdempotencyKey(array $tx): string
    {
        return 'bank_'.md5(implode('|', [
            $tx['Номер'] ?? '', $tx['Дата'] ?? '', $tx['Сумма'] ?? '',
            $tx['ПлательщикИНН'] ?? '', $tx['ПолучательИНН'] ?? '',
            $tx['НазначениеПлатежа'] ?? '',
        ]));
    }
}
