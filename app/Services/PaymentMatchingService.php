<?php

namespace App\Services;

use App\Models\BankStatementTransaction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Upd;
use Illuminate\Support\Facades\Log;

class PaymentMatchingService
{
    /**
     * Сопоставить транзакцию с документами и контрагентом
     */
    public function matchTransaction(BankStatementTransaction $transaction): void
    {
        $purpose = $transaction->purpose ?? '';
        $amount = (float) $transaction->amount;

        // 1. Ищем контрагента
        $company = $this->findCompany($transaction);

        if ($company) {
            $transaction->matched_company_id = $company->id;
            $transaction->save();
        }

        // 2. Парсим номера документов из назначения платежа
        $matchedDocs = $this->parseDocumentNumbers($purpose);

        if (!empty($matchedDocs)) {
            // Приоритет: УПД > счёт > договор > заказ
            $found = false;

            // Ищем УПД
            foreach ($matchedDocs['upd_numbers'] ?? [] as $number) {
                $upd = $this->findUpd($number, $company);
                if ($upd) {
                    $this->attachDocument($transaction, 'upd', $upd->id, $amount);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Ищем счёт
                foreach ($matchedDocs['invoice_numbers'] ?? [] as $number) {
                    $invoice = $this->findInvoice($number, $company);
                    if ($invoice) {
                        $this->attachDocument($transaction, 'invoice', $invoice->id, $amount);
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                // Ищем заказ
                foreach ($matchedDocs['order_numbers'] ?? [] as $number) {
                    $order = $this->findOrder($number, $company);
                    if ($order) {
                        $this->attachDocument($transaction, 'order', $order->id, $amount);
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) {
                return; // Успешно сопоставлено
            }
        }

        // 3. Если не сопоставилось — пробуем по сумме и дате
        if ($company) {
            $matchedByAmount = $this->matchByAmountAndDate($transaction, $company);
            if ($matchedByAmount) {
                return;
            }
        }

        // 4. Если контрагент не найден — создаём внешнюю компанию
        if (!$company) {
            $company = $this->createExternalCompany($transaction);
            if ($company) {
                $transaction->matched_company_id = $company->id;
                $transaction->save();
            }
        }

        // 5. Помечаем как не сопоставленное
        $transaction->is_unmatched = true;
        $transaction->unmatched_reason = $company
            ? 'Документ не найден'
            : 'Контрагент не определён';
        $transaction->save();
    }

    /**
     * Поиск контрагента по ИНН или названию
     */
    private function findCompany(BankStatementTransaction $transaction): ?Company
    {
        $inn = $transaction->payer_inn ?? $transaction->payee_inn;
        $name = $transaction->payer_name ?? $transaction->payee_name;

        // По ИНН
        if ($inn) {
            $company = Company::where('inn', $inn)->first();
            if ($company) {
                Log::debug('Контрагент найден по ИНН', ['inn' => $inn, 'company_id' => $company->id]);
                return $company;
            }
        }

        // По названию (очищаем кавычки, ООО/ИП и т.д.)
        if ($name) {
            $cleanName = $this->cleanCompanyName($name);
            $company = Company::where('legal_name', 'like', "%{$cleanName}%")
                ->orWhere('legal_name', 'like', "%{$name}%")
                ->first();
            if ($company) {
                Log::debug('Контрагент найден по названию', ['name' => $name, 'company_id' => $company->id]);
                return $company;
            }
        }

        return null;
    }

    /**
     * Парсинг номеров документов из назначения платежа
     */
    private function parseDocumentNumbers(string $purpose): array
    {
        $result = [
            'upd_numbers' => [],
            'invoice_numbers' => [],
            'order_numbers' => [],
        ];

        // УПД: "УПД №123", "счет-фактура №456", "УПД-789"
        if (preg_match_all('/(?:УПД|счет-фактура|с\/ф)\s*(?:№|N|#)?\s*(\d+)/ui', $purpose, $matches)) {
            $result['upd_numbers'] = array_unique($matches[1]);
        }

        // Счета: "счёт №123", "счет №456", "invoice №789"
        if (preg_match_all('/(?:счёт|счет|invoice)\s*(?:№|N|#)?\s*(\d+)/ui', $purpose, $matches)) {
            $result['invoice_numbers'] = array_unique($matches[1]);
        }

        // Заказы: "заказ №123", "order №456"
        if (preg_match_all('/(?:заказ|order)\s*(?:№|N|#)?\s*(\d+)/ui', $purpose, $matches)) {
            $result['order_numbers'] = array_unique($matches[1]);
        }

        // Договоры: "договор №123", "дог. №456"
        if (preg_match_all('/(?:договор|дог|contract)\s*(?:№|N|#)?\s*(\d+)/ui', $purpose, $matches)) {
            $result['order_numbers'] = array_unique(array_merge($result['order_numbers'], $matches[1]));
        }

        return $result;
    }

    /**
     * Поиск УПД по номеру
     */
    private function findUpd(string $number, ?Company $company): ?Upd
    {
        $query = Upd::where('number', $number)
            ->orWhere('id', $number);

        if ($company) {
            $query->where(function ($q) use ($company) {
                $q->where('lessee_company_id', $company->id)
                  ->orWhere('lessor_company_id', $company->id);
            });
        }

        return $query->first();
    }

    /**
     * Поиск счёта по номеру
     */
    private function findInvoice(string $number, ?Company $company): ?Invoice
    {
        $query = Invoice::where('number', $number)
            ->orWhere('id', $number);

        if ($company) {
            $query->where('company_id', $company->id);
        }

        return $query->first();
    }

    /**
     * Поиск заказа по номеру
     */
    private function findOrder(string $number, ?Company $company): ?Order
    {
        $query = Order::where('company_order_number', $number)
            ->orWhere('id', $number);

        if ($company) {
            $query->where(function ($q) use ($company) {
                $q->where('lessee_company_id', $company->id)
                  ->orWhere('lessor_company_id', $company->id);
            });
        }

        return $query->first();
    }

    /**
     * Привязка документа к транзакции
     */
    private function attachDocument(BankStatementTransaction $transaction, string $type, int $docId, float $amount): void
    {
        $transaction->document_type = $type;
        $transaction->document_id = $docId;
        $transaction->is_unmatched = false;
        $transaction->unmatched_reason = null;
        $transaction->save();

        // Обновляем статус
        $transaction->status = 'processed';

        if ($type === 'upd') {
            $transaction->status = 'processed';
            Log::info("Транзакция #{$transaction->id} сопоставлена с УПД #{$docId}");
        } elseif ($type === 'invoice') {
            $transaction->invoice_id = $docId;
            Log::info("Транзакция #{$transaction->id} сопоставлена со счётом #{$docId}");
        } else {
            Log::info("Транзакция #{$transaction->id} сопоставлена с заказом #{$docId}");
        }

        $transaction->save();
    }

    /**
     * Сопоставление по сумме и дате (если номер не найден)
     */
    private function matchByAmountAndDate(BankStatementTransaction $transaction, Company $company): bool
    {
        $amount = (float) $transaction->amount;
        $date = $transaction->date;
        $tolerance = 1.0; // погрешность ±1 рубль

        // Ищем УПД с совпадающей суммой за ±5 дней
        $upd = Upd::where(function ($q) use ($company) {
                $q->where('lessee_company_id', $company->id)
                  ->orWhere('lessor_company_id', $company->id);
            })
            ->whereBetween('issue_date', [$date->copy()->subDays(5), $date->copy()->addDays(5)])
            ->whereBetween('total_amount', [$amount - $tolerance, $amount + $tolerance])
            ->orderBy('issue_date', 'desc')
            ->first();

        if ($upd) {
            $this->attachDocument($transaction, 'upd', $upd->id, $amount);
            return true;
        }

        return false;
    }

    /**
     * Создание внешней компании
     */
    private function createExternalCompany(BankStatementTransaction $transaction): ?Company
    {
        $name = $transaction->payer_name ?? $transaction->payee_name ?? 'Неизвестный контрагент';
        $inn = $transaction->payer_inn ?? $transaction->payee_inn ?? null;

        try {
            $company = Company::create([
                'legal_name' => $name,
                'inn' => $inn,
                'is_external' => true,
                'is_lessor' => false,
                'is_lessee' => false,
                'status' => 'pending',
            ]);

            Log::info("Создана внешняя компания #{$company->id}: {$name}");
            return $company;
        } catch (\Exception $e) {
            Log::error("Ошибка создания внешней компании: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Очистка названия компании от кавычек, ООО, ИП и т.д.
     */
    private function cleanCompanyName(string $name): string
    {
        $name = preg_replace('/["«»"]/', '', $name);
        $name = preg_replace('/\s*(ООО|ИП|АО|ЗАО|ПАО)\s*/ui', '', $name);
        $name = trim($name);
        return $name;
    }
}
