<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankStatement;
use App\Models\BankStatementTransaction;
use App\Models\PendingPayout;
use App\Models\PendingTransaction;
use App\Models\RefundTransaction;
use App\Services\BalanceService;
use App\Services\BankStatementProcessingService;
use App\Services\Parsers\BankStatementParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankStatementController extends Controller
{
    public function index()
    {
        $statements = BankStatement::with('processedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Добавляем подсчет отложенных транзакций
        foreach ($statements as $statement) {
            $statement->pending_count = PendingTransaction::where('bank_statement_id', $statement->id)->count() +
                                    PendingPayout::whereHas('transaction', function ($q) use ($statement) {
                                        $q->where('bank_statement_id', $statement->id);
                                    })->count() +
                                    RefundTransaction::where('bank_statement_id', $statement->id)->count();
        }

        $pendingCount = PendingTransaction::count() + PendingPayout::count() + RefundTransaction::count();

        return view('admin.finance.bank-statements.index', compact('statements', 'pendingCount'));
    }

    public function create()
    {
        return view('admin.finance.bank-statements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'statement' => 'required|file|mimes:txt',
            'bank_name' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('statement');

            // Читаем файл как бинарные данные
            $content = file_get_contents($file->getRealPath());

            \Log::debug('File uploaded', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            $parser = new BankStatementParser;
            $transactions = $parser->parse($content);

            \Log::info('Parsed transactions', [
                'count' => count($transactions),
                'first_few' => array_slice($transactions, 0, 3),
            ]);

            // Если транзакций нет, прерываем выполнение с ошибкой
            if (count($transactions) === 0) {
                throw new \Exception('Не удалось распознать транзакции в файле выписки. Проверьте кодировку и формат файла.');
            }

            $statement = BankStatement::create([
                'filename' => $file->getClientOriginalName(),
                'bank_name' => $request->bank_name,
                'transactions_count' => count($transactions),
                'processed_by' => auth()->id(),
                'status' => 'processing',
            ]);

            $processingService = new BankStatementProcessingService(app(BalanceService::class));

            $processedCount = 0;
            $errorCount = 0;

            // Обрабатываем каждую транзакцию
            foreach ($transactions as $index => $transactionData) {
                try {
                    \Log::debug("Processing transaction {$index}", [
                        'data' => [
                            'Номер' => $transactionData['Номер'] ?? '',
                            'Дата' => $transactionData['Дата'] ?? '',
                            'Сумма' => $transactionData['Сумма'] ?? '',
                            'ПлательщикИНН' => $transactionData['ПлательщикИНН'] ?? '',
                            'ПолучательИНН' => $transactionData['ПолучательИНН'] ?? '',
                            'ТипДокумента' => $transactionData['ТипДокумента'] ?? '',
                        ]
                    ]);

                    // Валидация обязательных полей
                    if (empty($transactionData['Дата']) || empty($transactionData['Сумма'])) {
                        throw new \Exception('Отсутствуют обязательные поля: Дата или Сумма');
                    }

                    $processingService->processTransaction($transactionData, $statement->id);
                    $processedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    \Log::error('Ошибка обработки транзакции', [
                        'transaction_index' => $index,
                        'transaction' => [
                            'Номер' => $transactionData['Номер'] ?? '',
                            'Дата' => $transactionData['Дата'] ?? '',
                            'Сумма' => $transactionData['Сумма'] ?? '',
                            'ПлательщикИНН' => $transactionData['ПлательщикИНН'] ?? '',
                            'ПолучательИНН' => $transactionData['ПолучательИНН'] ?? '',
                        ],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Создаем запись об ошибке с гарантированно валидными значениями
                    try {
                        BankStatementTransaction::create([
                            'bank_statement_id' => $statement->id,
                            'date' => !empty($transactionData['Дата']) ?
                                \Carbon\Carbon::createFromFormat('d.m.Y', $transactionData['Дата'])->format('Y-m-d') :
                                now()->format('Y-m-d'),
                            'amount' => $this->validateAndParseAmount($transactionData['Сумма'] ?? 0),
                            'type' => 'unknown',
                            'payer_name' => $transactionData['Плательщик1'] ?? 'Не указан',
                            'payer_inn' => $this->cleanInn($transactionData['ПлательщикИНН'] ?? ''),
                            'payer_account' => $transactionData['ПлательщикСчет'] ?? '',
                            'payer_bic' => $transactionData['ПлательщикБИК'] ?? '',
                            'payee_name' => $transactionData['Получатель1'] ?? 'Не указан',
                            'payee_inn' => $this->cleanInn($transactionData['ПолучательИНН'] ?? ''),
                            'payee_account' => $transactionData['ПолучательСчет'] ?? '',
                            'payee_bic' => $transactionData['ПолучательБИК'] ?? '',
                            'purpose' => $transactionData['НазначениеПлатежа'] ?? 'Не указано',
                            'status' => 'error',
                            'error_message' => substr($e->getMessage(), 0, 500),
                        ]);
                    } catch (\Exception $creationError) {
                        \Log::critical('Не удалось создать запись об ошибке', [
                            'original_error' => $e->getMessage(),
                            'creation_error' => $creationError->getMessage(),
                            'transaction_data' => $transactionData,
                        ]);

                        // Создаем минимальную запись с гарантированно валидными значениями
                        BankStatementTransaction::create([
                            'bank_statement_id' => $statement->id,
                            'date' => now()->format('Y-m-d'),
                            'amount' => 0,
                            'type' => 'incoming',
                            'payer_name' => 'Ошибка обработки',
                            'payer_inn' => '0000000000',
                            'payer_account' => '00000000000000000000',
                            'payer_bic' => '000000000',
                            'payee_name' => 'Ошибка обработки',
                            'payee_inn' => '0000000000',
                            'payee_account' => '00000000000000000000',
                            'payee_bic' => '000000000',
                            'purpose' => 'Не удалось обработать транзакцию',
                            'status' => 'error',
                            'error_message' => substr('Ошибка: '.$e->getMessage().' | Creation: '.$creationError->getMessage(), 0, 500),
                        ]);
                    }
                }
            }

            DB::commit();

            // Обновляем статус выписки
            $this->updateStatementStatus($statement);

            \Log::info('Bank statement processing completed', [
                'statement_id' => $statement->id,
                'processed' => $processedCount,
                'errors' => $errorCount,
                'total_transactions' => count($transactions),
            ]);

            $message = "Выписка обработана. Обработано: {$processedCount}, Ошибок: {$errorCount}";

            if ($errorCount > 0) {
                return redirect()->route('admin.bank-statements.index')
                    ->with('warning', $message);
            }

            return redirect()->route('admin.bank-statements.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Критическая ошибка обработки выписки', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_info' => [
                    'name' => $file->getClientOriginalName() ?? 'unknown',
                    'size' => $file->getSize() ?? 0,
                ]
            ]);

            // Если выписка была создана, но произошла ошибка - обновляем статус
            if (isset($statement)) {
                $statement->update(['status' => 'failed']);
            }

            return redirect()->back()
                ->with('error', 'Критическая ошибка обработки выписки: '.$e->getMessage());
        }
    }

    /**
     * Вспомогательный метод для обработки суммы
     */
    private function validateAndParseAmount($amount): float
    {
        if (is_string($amount)) {
            $amount = str_replace([' ', ','], ['', '.'], trim($amount));
        }

        if (!is_numeric($amount)) {
            return 0.0;
        }

        $parsedAmount = (float) $amount;
        return $parsedAmount >= 0 ? $parsedAmount : 0.0;
    }

    /**
     * Очистка ИНН от нечисловых символов
     */
    private function cleanInn(?string $inn): string
    {
        if (empty($inn)) {
            return '0000000000';
        }

        $cleaned = preg_replace('/[^0-9]/', '', $inn);
        return !empty($cleaned) ? $cleaned : '0000000000';
    }

    protected function updateStatementStatus(BankStatement $statement): void
    {
        $processedCount = BankStatementTransaction::where('bank_statement_id', $statement->id)
            ->where('status', 'processed')
            ->count();

        $errorCount = BankStatementTransaction::where('bank_statement_id', $statement->id)
            ->where('status', 'error')
            ->count();

        $totalProcessed = $processedCount + $errorCount;

        // Унифицированная логика определения статуса
        if ($totalProcessed === 0 && $statement->transactions_count > 0) {
            $status = 'processing';
        } elseif ($totalProcessed === 0) {
            $status = 'failed';
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

        \Log::info('Statement status updated', [
            'statement_id' => $statement->id,
            'status' => $status,
            'processed' => $processedCount,
            'errors' => $errorCount,
            'total_processed' => $totalProcessed,
            'transactions_count' => $statement->transactions_count,
        ]);
    }

    public function show(BankStatement $bankStatement)
    {
        // Загружаем транзакции с связанными данными
        $bankStatement->load([
            'transactions.company',
            'transactions.invoice',
            'transactions.transactionEntry',
            'processedBy',
        ]);

        return view('admin.finance.bank-statements.show', compact('bankStatement'));
    }

    public function destroy(BankStatement $bankStatement)
    {
        try {
            DB::beginTransaction();

            $bankStatement->transactions()->delete();
            $bankStatement->delete();

            DB::commit();

            return redirect()->route('admin.bank-statements.index')
                ->with('success', 'Выписка и все связанные транзакции удалены.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Ошибка удаления выписки: '.$e->getMessage());
        }
    }

    public function pendingTransactions()
    {
        $pendingTransactions = PendingTransaction::with('bankStatement')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $pendingPayouts = PendingPayout::with(['transaction.bankStatement'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $refundTransactions = RefundTransaction::with('bankStatement')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Добавьте логирование для отладки
        \Log::debug('Pending data', [
            'transactions' => $pendingTransactions->count(),
            'payouts' => $pendingPayouts->count(),
            'refunds' => $refundTransactions->count(),
        ]);

        return view('admin.finance.bank-statements.pending',
            compact('pendingTransactions', 'pendingPayouts', 'refundTransactions'));
    }

    public function processPendingTransaction(Request $request, PendingTransaction $pendingTransaction)
    {
        try {
            DB::beginTransaction();

            // Находим или создаем компанию
            $company = Company::where('inn', $pendingTransaction->company_inn)->first();

            if (! $company) {
                // Перенаправляем на страницу создания компании с предзаполненными данными
                return redirect()->route('admin.companies.create', [
                    'inn' => $pendingTransaction->company_inn,
                    'legal_name' => $pendingTransaction->company_name,
                    'from_pending' => $pendingTransaction->id,
                ]);
            }

            // Обрабатываем транзакцию
            $processingService = new BankStatementProcessingService(app(BalanceService::class));
            $processingService->processTransaction(
                $pendingTransaction->transaction_data,
                $pendingTransaction->bank_statement_id
            );

            $pendingTransaction->update([
                'status' => 'processed',
                'notes' => 'Обработано администратором: '.auth()->user()->name,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Транзакция успешно обработана');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Ошибка обработки: '.$e->getMessage());
        }
    }

    public function cancelPendingPayout(Request $request, PendingPayout $pendingPayout)
    {
        try {
            $pendingPayout->update([
                'status' => 'cancelled',
                'notes' => 'Отменено администратором: '.auth()->user()->name,
            ]);

            return redirect()->back()->with('success', 'Выплата успешно отменена');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка отмены выплаты: '.$e->getMessage());
        }
    }

    public function processRefund(Request $request)
    {
        $request->validate([
            'refund_id' => 'required|exists:refund_transactions,id',
            'action' => 'required|in:process,cancel',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $refund = RefundTransaction::findOrFail($request->refund_id);

            if ($request->action === 'process') {
                // Логика обработки возврата
                $this->processRefundTransaction($refund);
                $message = 'Возврат успешно обработан';
            } else {
                // Отмена возврата
                $refund->update([
                    'status' => 'cancelled',
                    'notes' => $request->notes,
                ]);
                $message = 'Возврат отклонен';
            }

            DB::commit();

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Ошибка обработки возврата: '.$e->getMessage());
        }
    }

    protected function processRefundTransaction(RefundTransaction $refund)
    {
        // Находим компанию
        $company = Company::where('inn', $refund->company_inn)->first();

        if (! $company) {
            throw new \Exception("Компания с ИНН {$refund->company_inn} не найдена");
        }

        // Определяем тип операции
        $transactionType = $refund->type === 'refund_incoming' ? 'debit' : 'credit';
        $purpose = $refund->type === 'refund_incoming' ? 'lessor_refund' : 'lessee_refund';

        // Создаем финансовую проводку
        $entry = app(BalanceService::class)->commitTransaction(
            $company,
            $refund->amount,
            $transactionType,
            $purpose,
            null,
            "Возврат средств: {$refund->purpose}",
            'refund_'.$refund->id.'_'.time()
        );

        // Обновляем статус возврата
        $refund->update([
            'status' => 'processed',
            'notes' => 'Обработано администратором: '.auth()->user()->name,
        ]);
    }
}
