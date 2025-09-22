<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentProcessingService
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Обработать платеж из банковской выписки
     *
     * @param  string  $payerInn  ИНН плательщика
     * @param  float  $amount  Сумма платежа
     * @param  string  $paymentDate  Дата платежа
     * @param  string  $purpose  Назначение платежа
     * @param  string  $idempotencyKey  Ключ идемпотентности для предотвращения дублей
     *
     * @throws \Exception
     */
    public function processPayment(
        string $payerInn,
        float $amount,
        string $paymentDate,
        string $purpose,
        string $idempotencyKey
    ): void {
        // Проверяем, не обрабатывался ли уже этот платеж
        if (TransactionEntry::where('idempotency_key', $idempotencyKey)->exists()) {
            Log::warning('Платеж уже был обработан ранее', ['idempotency_key' => $idempotencyKey]);

            return;
        }

        // Ищем счет по назначению платежа
        $invoice = $this->findInvoiceByPurpose($purpose);
        if (! $invoice) {
            Log::error('Счет не найден для платежа', ['purpose' => $purpose, 'amount' => $amount]);
            throw new \Exception("Не удалось идентифицировать счет для платежа с назначением: $purpose");
        }

        // Проверяем, что ИНН плательщика соответствует ИНН компании-арендатора
        if ($invoice->company->inn !== $payerInn) {
            Log::error('ИНН плательщика не совпадает с ИНН компании по счету', [
                'payer_inn' => $payerInn,
                'company_inn' => $invoice->company->inn,
                'invoice_id' => $invoice->id,
            ]);
            throw new \Exception('ИНН плательщика не совпадает с ИНН компании по счету.');
        }

        try {
            DB::beginTransaction();

            // Используем BalanceService для создания проводки
            $this->balanceService->commitTransaction(
                $invoice->company,
                $amount,
                'debit',
                TransactionEntry::PURPOSE_LESSEE_PAYMENT,
                $invoice,
                "Оплата по счету №{$invoice->number}. Назначение: $purpose",
                $idempotencyKey
            );

            // Обновляем статус счета
            $invoice->amount_paid += $amount;
            $invoice->updateStatus();
            $invoice->save();

            DB::commit();

            Log::info('Платеж успешно обработан', [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка обработки платежа', [
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Не удалось обработать платеж: '.$e->getMessage());
        }
    }

    /**
     * Поиск счета по назначению платежа
     * (может быть реализовано по-разному: поиск по номеру счета, по номеру заказа и т.д.)
     */
    protected function findInvoiceByPurpose(string $purpose): ?Invoice
    {
        // Пример: ищем номер счета в назначении платежа
        if (preg_match('/СЧ-(\d{8}-\d{4})/', $purpose, $matches)) {
            $invoiceNumber = $matches[0];

            return Invoice::where('number', $invoiceNumber)->first();
        }

        // Дополнительные логики поиска...
        return null;
    }
}
