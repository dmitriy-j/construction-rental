<?php

namespace App\Services;

use App\Models\BankStatementTransaction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PendingPayout;
use App\Models\PendingTransaction;
use App\Models\RefundTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankStatementProcessingService
{
    protected $balanceService;

    protected $platformInn;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
        $this->platformInn = config('platform.inn', '9723125209');
    }

    public function processTransaction(array $transactionData, int $bankStatementId): void
    {
        try {
            // Определяем тип транзакции
            $type = $this->determineTransactionType($transactionData);
            $idempotencyKey = $this->generateIdempotencyKey($transactionData);

            // Если это возврат, создаем отложенную транзакцию
            if (in_array($type, ['refund_incoming', 'refund_outgoing'])) {
                $this->handleRefundTransaction($transactionData, $type, $bankStatementId);

                return;
            }

            // Проверка на дубликат
            if (BankStatementTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                \Log::warning('Попытка обработки дублирующей транзакции', ['idempotency_key' => $idempotencyKey]);

                return;
            }

            // Валидация и преобразование суммы
            $amount = $this->validateAndParseAmount($transactionData['Сумма']);

            // Логируем информацию о транзакции для отладки
            \Log::debug('Данные транзакции для обработки', [
                'type' => $type,
                'payer_inn' => $transactionData['ПлательщикИНН'] ?? '',
                'payee_inn' => $transactionData['ПолучательИНН'] ?? '',
                'cleaned_payer_inn' => $this->cleanInn($transactionData['ПлательщикИНН'] ?? ''),
                'cleaned_payee_inn' => $this->cleanInn($transactionData['ПолучательИНН'] ?? ''),
            ]);

            $company = $this->findCompany($transactionData, $type, $bankStatementId);

            // Если компания не найдена, просто возвращаемся (она уже обработана в findCompany)
            if (! $company) {
                return;
            }

            $invoice = $this->findInvoice($transactionData, $type);

            // Преобразуем дату
            try {
                $date = \Carbon\Carbon::createFromFormat('d.m.Y', $transactionData['Дата'])->format('Y-m-d');
            } catch (\Exception $e) {
                $date = now()->format('Y-m-d');
            }

            // Создаем запись о транзакции
            $transaction = BankStatementTransaction::create([
                'bank_statement_id' => $bankStatementId,
                'date' => $date,
                'amount' => $amount,
                'type' => $type,
                'payer_name' => $transactionData['Плательщик1'] ?? 'Не указан',
                'payer_inn' => $this->cleanInn($transactionData['ПлательщикИНН'] ?? ''),
                'payer_account' => $transactionData['ПлательщикСчет'] ?? '',
                'payer_bic' => $transactionData['ПлательщикБИК'] ?? '',
                'payee_name' => $transactionData['Получатель1'] ?? 'Не указан',
                'payee_inn' => $this->cleanInn($transactionData['ПолучательИНН'] ?? ''),
                'payee_account' => $transactionData['ПолучательСчет'] ?? '',
                'payee_bic' => $transactionData['ПолучательБИК'] ?? '',
                'purpose' => $transactionData['НазначениеПлатежа'] ?? 'Не указано',
                'idempotency_key' => $idempotencyKey,
                'company_id' => $company->id,
                'invoice_id' => $invoice?->id,
                'status' => 'pending',
            ]);

            $this->processFinancialOperation($transaction, $company, $invoice);

        } catch (\Exception $e) {
            \Log::error('Полная ошибка создания транзакции', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'transaction_data' => $transactionData,
                'bank_statement_id' => $bankStatementId,
            ]);

            try {
                // Генерируем idempotency_key для записи об ошибке
                $errorIdempotencyKey = 'error_'.$this->generateIdempotencyKey($transactionData);

                // Преобразуем дату
                $date = isset($transactionData['Дата']) ?
                    \Carbon\Carbon::createFromFormat('d.m.Y', $transactionData['Дата'])->format('Y-m-d') :
                    now()->format('Y-m-d');

                // Создаем запись об ошибке с гарантированно валидными значениями
                BankStatementTransaction::create([
                    'bank_statement_id' => $bankStatementId,
                    'date' => $date,
                    'amount' => 0,
                    'type' => $type ?? 'incoming',
                    'payer_name' => $transactionData['Плательщик1'] ?? 'Не указан',
                    'payer_inn' => $this->cleanInn($transactionData['ПлательщикИНН'] ?? ''),
                    'payer_account' => $transactionData['ПлательщикСчет'] ?? '',
                    'payer_bic' => $transactionData['ПлательщикБИК'] ?? '',
                    'payee_name' => $transactionData['Получатель1'] ?? 'Не указан',
                    'payee_inn' => $this->cleanInn($transactionData['ПолучательИНН'] ?? ''),
                    'payee_account' => $transactionData['ПолучательСчет'] ?? '',
                    'payee_bic' => $transactionData['ПолучательБИК'] ?? '',
                    'purpose' => $transactionData['НазначениеПлатежа'] ?? 'Не указано',
                    'idempotency_key' => $errorIdempotencyKey,
                    'status' => 'error',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);
            } catch (\Exception $creationError) {
                \Log::critical('Не удалось создать запись об ошибке', [
                    'original_error' => $e->getMessage(),
                    'creation_error' => $creationError->getMessage(),
                    'transaction_data' => $transactionData,
                ]);
            }
        }
    }

    protected function handleRefundTransaction(array $transactionData, string $type, int $bankStatementId): void
    {
        try {
            $inn = $type === 'refund_incoming'
                ? $this->cleanInn($transactionData['ПлательщикИНН'] ?? '')
                : $this->cleanInn($transactionData['ПолучательИНН'] ?? '');

            $name = $type === 'refund_incoming'
                ? $transactionData['Плательщик1'] ?? 'Неизвестный плательщик'
                : $transactionData['Получатель1'] ?? 'Неизвестный получатель';

            // Создаем запись о возврате
            RefundTransaction::create([
                'bank_statement_id' => $bankStatementId,
                'company_inn' => $inn,
                'company_name' => $name,
                'amount' => $this->validateAndParseAmount($transactionData['Сумма']),
                'type' => $type,
                'transaction_data' => $transactionData,
                'status' => 'pending',
                'purpose' => $transactionData['НазначениеПлатежа'] ?? '',
            ]);

            \Log::warning('Обнаружен возврат средств', [
                'inn' => $inn,
                'name' => $name,
                'type' => $type,
            ]);

        } catch (\Exception $e) {
            \Log::error('Ошибка обработки возврата', [
                'transaction' => $transactionData,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Остальные методы остаются без изменений
    protected function validateAndParseAmount($amount): float
    {
        // Преобразуем в число, даже если это строка
        if (is_string($amount)) {
            $amount = str_replace(',', '.', $amount);
        }

        if (! is_numeric($amount)) {
            throw new \Exception("Некорректный формат суммы: {$amount}");
        }

        $parsedAmount = (float) $amount;

        if ($parsedAmount <= 0) {
            throw new \Exception("Сумма должна быть положительной: {$amount}");
        }

        return $parsedAmount;
    }

    protected function generateIdempotencyKey(array $transactionData): string
    {
        // Используем уникальные поля из выписки: Номер документа и Дату операции
        // Это гарантирует, что каждая строка выписки будет иметь уникальный ключ
        $uniqueSourceString = implode('|', [
            $transactionData['Номер'] ?? '', // Уникальный номер документа в выписке
            $transactionData['Дата'] ?? '',
            $transactionData['ДатаСписано'] ?? $transactionData['ДатаПоступило'] ?? '', // Используем дату операции
            $transactionData['Сумма'] ?? '',
            $transactionData['ПлательщикИНН'] ?? '',
            $transactionData['ПолучательИНН'] ?? '',
            $transactionData['НазначениеПлатежа'] ?? '',
        ]);

        return 'bank_stmt_'.md5($uniqueSourceString);
    }

    protected function determineTransactionType(array $transactionData): string
    {
        $payerInn = $this->cleanInn($transactionData['ПлательщикИНН'] ?? '');
        $payeeInn = $this->cleanInn($transactionData['ПолучательИНН'] ?? '');

        $purpose = strtolower($transactionData['НазначениеПлатежа'] ?? '');

        // Проверяем, является ли платеж возвратом
        $isReturn = preg_match('/возврат|переплата|излишне|вернуть|возмещение/', $purpose);

        if ($isReturn) {
            // Если это возврат, то определяем направление относительно платформы
            if ($payerInn === $this->platformInn) {
                return 'refund_outgoing'; // Платформа возвращает деньги
            } elseif ($payeeInn === $this->platformInn) {
                return 'refund_incoming'; // Платформе возвращают деньги
            }
        }

        // Стандартная логика
        if ($payerInn === $this->platformInn) {
            return 'outgoing';
        }

        if ($payeeInn === $this->platformInn) {
            return 'incoming';
        }

        return 'unknown';
    }

    protected function cleanInn(?string $inn): string
    {
        if (empty($inn)) {
            return '0000000000';
        }

        $cleaned = preg_replace('/[^0-9]/', '', $inn);

        return ! empty($cleaned) ? $cleaned : '0000000000';
    }

    protected function findCompany(array $transactionData, string $type, int $bankStatementId): ?Company
    {
        $inn = $type === 'incoming'
            ? $this->cleanInn($transactionData['ПлательщикИНН'] ?? '')
            : $this->cleanInn($transactionData['ПолучательИНН'] ?? '');

        // Проверяем, что ИНН валиден после очистки
        if ($inn === '0000000000' || strlen($inn) < 10) {
            \Log::warning('Неверный формат ИНН после очистки', [
                'original_inn' => $type === 'incoming' ? $transactionData['ПлательщикИНН'] ?? '' : $transactionData['ПолучательИНН'] ?? '',
                'cleaned_inn' => $inn,
            ]);

            return null;
        }

        $company = Company::where('inn', $inn)->first();

        if (! $company) {
            // Создаем отложенную транзакцию для незарегистрированной компании
            $this->handleUnregisteredCompany($transactionData, $type, $bankStatementId);

            return null;
        }

        return $company;
    }

    protected function findInvoice(array $transactionData, string $type): ?Invoice
    {
        if ($type !== 'incoming') {
            return null;
        }

        $purpose = $transactionData['НазначениеПлатежа'] ?? '';

        if (preg_match('/[Сс]чет[\s№]*([A-Za-zА-Яа-я\-0-9]+)/u', $purpose, $matches)) {
            $invoiceNumber = trim($matches[1]);

            return Invoice::where('number', $invoiceNumber)->first();
        }

        return null;
    }

    protected function processFinancialOperation(BankStatementTransaction $transaction, Company $company, ?Invoice $invoice): void
    {
        try {
            DB::beginTransaction();

            if (! $company) {
                $inn = $transaction->type === 'incoming' ? $transaction->payer_inn : $transaction->payee_inn;
                throw new \Exception("Компания с ИНН {$inn} не найдена в системе");
            }

            // Определяем роль компании в данной транзакции
            $role = $this->determineCompanyRole($company, [
                'НазначениеПлатежа' => $transaction->purpose,
            ], $transaction->type);

            if ($transaction->type === 'incoming') {
                $this->processIncomingTransaction($transaction, $company, $invoice, $role);
            } else {
                $this->processOutgoingTransaction($transaction, $company, $role);
            }

            $transaction->update(['status' => 'processed']);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $transaction->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Ошибка обработки транзакции', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function processIncomingTransaction(BankStatementTransaction $transaction, Company $company, ?Invoice $invoice, string $role): void
    {
        $purpose = $role === 'lessee' ? 'lessee_payment' : 'lessor_deposit';

        // Создаем финансовую проводку
        $entry = $this->balanceService->commitTransaction(
            $company,
            $transaction->amount,
            'debit',
            $purpose,
            $invoice,
            "Оплата по выписке: {$transaction->purpose}",
            $transaction->idempotency_key
        );

        // Обновляем связь с TransactionEntry
        $transaction->update([
            'source_type' => get_class($entry),
            'source_id' => $entry->id,
            'status' => 'processed',
        ]);

        if ($invoice && $role === 'lessee') {
            $invoice->amount_paid += $transaction->amount;
            $invoice->updateStatus();
            $invoice->save();
        }
    }

    protected function processOutgoingTransaction(BankStatementTransaction $transaction, Company $company): void
    {
        try {
            // Проверяем, зарегистрирован ли получатель
            $payeeInn = $this->cleanInn($transaction->payee_inn);
            $payeeCompany = Company::where('inn', $payeeInn)->first();

            if (! $payeeCompany) {
                // Создаем запись о pending payout
                PendingPayout::create([
                    'bank_statement_transaction_id' => $transaction->id,
                    'payee_inn' => $payeeInn,
                    'payee_name' => $transaction->payee_name,
                    'amount' => $transaction->amount,
                    'purpose' => $transaction->purpose,
                    'status' => 'pending_registration',
                ]);

                $transaction->update([
                    'status' => 'on_hold',
                    'error_message' => 'Получатель не зарегистрирован на платформе',
                ]);

                return;
            }

            // Стандартная обработка для зарегистрированного получателя
            $this->balanceService->commitTransaction(
                $company,
                $transaction->amount,
                'credit',
                'lessor_payout',
                null,
                "Выплата по выписке: {$transaction->purpose}",
                $transaction->idempotency_key
            );

        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Ошибка обработки исходящей транзакции', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function handleUnregisteredCompany(array $transactionData, string $type, int $bankStatementId): void
    {
        $inn = $type === 'incoming'
            ? $this->cleanInn($transactionData['ПлательщикИНН'] ?? '')
            : $this->cleanInn($transactionData['ПолучательИНН'] ?? '');

        $name = $type === 'incoming'
            ? $transactionData['Плательщик1'] ?? 'Неизвестный плательщик'
            : $transactionData['Получатель1'] ?? 'Неизвестный получатель';

        // Определяем правильный тип отложенной записи
        if ($type === 'outgoing') {
            // Для исходящих платежей создаем PendingPayout
            PendingPayout::create([
                'bank_statement_id' => $bankStatementId,
                'payee_inn' => $inn,
                'payee_name' => $name,
                'amount' => $this->validateAndParseAmount($transactionData['Сумма']),
                'purpose' => $transactionData['НазначениеПлатежа'] ?? '',
                'status' => 'pending_registration',
            ]);
        } else {
            // Для входящих платежей создаем PendingTransaction
            PendingTransaction::create([
                'bank_statement_id' => $bankStatementId,
                'company_inn' => $inn,
                'company_name' => $name,
                'amount' => $this->validateAndParseAmount($transactionData['Сумма']),
                'type' => $type,
                'transaction_data' => $transactionData,
                'status' => 'pending_registration',
            ]);
        }
    }

    protected function determineCompanyRole(Company $company, array $transactionData, string $type): string
    {
        // Если это входящий платеж на платформу, компания - арендатор
        if ($type === 'incoming') {
            return 'lessee';
        }

        // Если это исходящий платеж от платформы, компания - арендодатель
        if ($type === 'outgoing') {
            return 'lessor';
        }

        // Дополнительная логика определения на основе назначения платежа
        $purpose = strtolower($transactionData['НазначениеПлатежа'] ?? '');

        if (str_contains($purpose, 'аренд') || str_contains($purpose, 'рент')) {
            return $company->is_lessee ? 'lessee' : 'lessor';
        }

        if (str_contains($purpose, 'выплата') || str_contains($purpose, 'оплат')) {
            return $company->is_lessor ? 'lessor' : 'lessee';
        }

        // По умолчанию используем приоритетную роль
        return $company->is_lessor ? 'lessor' : 'lessee';
    }
}
