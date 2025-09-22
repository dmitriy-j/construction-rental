<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Models\TransactionEntry;
use App\Services\BalanceService;
use App\Services\InvoiceService;

class CreateInvoiceOnOrderConfirmation
{
    public function handle(OrderConfirmed $event)
    {
        $order = $event->order;

        try {
            // Создаем счет (предполагаем, что InvoiceService уже существует)
            $invoiceService = app(InvoiceService::class);
            $invoice = $invoiceService->createInvoice(
                $order,
                $order->lesseeCompany,
                $order->total_price,
                $order->platform_fee
            );

            // Создаем проводку для долга арендатора
            $balanceService = app(BalanceService::class);
            $balanceService->commitTransaction(
                $order->lesseeCompany,
                $order->total_price,
                'credit',
                TransactionEntry::PURPOSE_LESSEE_PAYMENT,
                $invoice,
                "Счет на оплату заказа №{$order->id}",
                'order_invoice_'.$order->id
            );

        } catch (\Exception $e) {
            \Log::error('Ошибка создания счета при подтверждении заказа', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
