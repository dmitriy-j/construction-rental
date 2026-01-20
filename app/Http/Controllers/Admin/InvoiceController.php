<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Upd;
use App\Services\InvoiceGeneratorService;
use App\Services\DocumentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    protected $invoiceGeneratorService;

    public function __construct(InvoiceGeneratorService $invoiceGeneratorService)
    {
        $this->invoiceGeneratorService = $invoiceGeneratorService;
    }

    /**
     * Список всех счетов
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['order', 'company', 'upd'])
            ->orderBy('created_at', 'desc');

        // Фильтрация по статусу
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20);

        $statuses = [
            'all' => 'Все статусы',
            Invoice::STATUS_DRAFT => 'Черновик',
            Invoice::STATUS_SENT => 'Отправлен',
            Invoice::STATUS_VIEWED => 'Просмотрен',
            Invoice::STATUS_PAID => 'Оплачен',
            Invoice::STATUS_OVERDUE => 'Просрочен',
            Invoice::STATUS_CANCELED => 'Отменен',
        ];

        return view('admin.invoices.index', compact('invoices', 'statuses'));
    }

    /**
     * Просмотр счета
     */
    public function show(Invoice $invoice)
    {
        // ГАРАНТИРОВАННАЯ ЗАГРУЗКА ВСЕХ НЕОБХОДИМЫХ ОТНОШЕНИЙ
        $invoice->load([
            'order.items.equipment',
            'company', // Загружаем компанию-плательщика
            'upd.waybill.equipment',
            'items'
        ]);

        // Используем DocumentDataService для подготовки данных для отображения
        $documentDataService = app(DocumentDataService::class);
        $invoiceData = $documentDataService->prepareInvoiceDataForDisplay($invoice);

        return view('admin.documents.invoices.show', [
            'document' => $invoice,
            'invoice_data' => $invoiceData // Передаем подготовленные данные
        ]);
    }

    /**
     * Создание предоплатного счета для заказа
     */
    public function createForOrder(Order $order)
    {
        try {
            $result = $this->invoiceGeneratorService->generateAdvanceInvoice($order);

            return redirect()->route('admin.invoices.show', $result['invoice'])
                           ->with('success', 'Предоплатный счет успешно создан');

        } catch (\Exception $e) {
            Log::error('Ошибка создания счета для заказа', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Ошибка создания счета: ' . $e->getMessage());
        }
    }

    /**
     * Создание постоплатного счета для УПД
     */
    public function createForUpd(Upd $upd)
    {
        try {
            $result = $this->invoiceGeneratorService->generatePostpaymentInvoice($upd);

            return redirect()->route('admin.invoices.show', $result['invoice'])
                           ->with('success', 'Постоплатный счет успешно создан');

        } catch (\Exception $e) {
            Log::error('Ошибка создания счета для УПД', [
                'upd_id' => $upd->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Ошибка создания счета: ' . $e->getMessage());
        }
    }

    /**
     * Скачать файл счета
     */
    public function download(Invoice $invoice)
    {
        try {
            Log::info('Генерация и скачивание счета на лету', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'user_id' => auth()->id(),
            ]);

            // ГАРАНТИРОВАННАЯ ЗАГРУЗКА ВСЕХ НЕОБХОДИМЫХ ОТНОШЕНИЙ
            $invoice->load([
                'order.items.equipment',
                'company',
                'upd.waybill.equipment',
                'items'
            ]);

            // Получаем шаблон
            $scenario = $invoice->upd_id
                ? \App\Models\DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD
                : \App\Models\DocumentTemplate::INVOICE_SCENARIO_ADVANCE_ORDER;

            $template = \App\Models\DocumentTemplate::active()
                ->byType(\App\Models\DocumentTemplate::TYPE_INVOICE)
                ->byScenario($scenario)
                ->first();

            if (!$template) {
                throw new \Exception("Шаблон для сценария {$scenario} не найден");
            }

            // Подготавливаем данные
            $invoiceData = $this->invoiceGeneratorService->prepareInvoiceDataForDownload($invoice);

            // Генерируем файл В ПАМЯТИ
            $documentGeneratorService = app(\App\Services\DocumentGeneratorService::class);
            $fileContent = $documentGeneratorService->generateDocumentInMemory($template, $invoiceData);

            if (empty($fileContent)) {
                throw new \Exception('Не удалось сгенерировать содержимое файла');
            }

            // Создаем безопасное имя файла
            $filename = $this->generateSafeFilename($invoice->number);

            Log::info('Счет успешно сгенерирован в памяти для скачивания', [
                'invoice_id' => $invoice->id,
                'file_size' => strlen($fileContent),
                'filename' => $filename
            ]);

            // Возвращаем файл для скачивания прямо из памяти
            return response()->streamDownload(function () use ($fileContent) {
                echo $fileContent;
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка генерации счета для скачивания', [
                'invoice_id' => $invoice->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Ошибка при генерации счета: ' . $e->getMessage());
        }
    }

    /**
     * Генерация безопасного имени файла
     */
    protected function generateSafeFilename(string $invoiceNumber): string
    {
        // Заменяем недопустимые символы
        $safeName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $invoiceNumber);

        // Добавляем префикс и расширение
        return 'Счет_' . $safeName . '.xlsx';
    }

    /**
     * Отменить счет
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500'
        ]);

        try {
            $invoice->cancel($request->reason);

            return redirect()->back()->with('success', 'Счет успешно отменен');

        } catch (\Exception $e) {
            Log::error('Ошибка отмены счета', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Ошибка отмены счета: ' . $e->getMessage());
        }
    }

    /**
     * Удалить счет
     */
    public function destroy(Invoice $invoice)
    {
        try {
            $success = $this->invoiceGeneratorService->deleteInvoice($invoice);

            if ($success) {
                return redirect()->route('admin.invoices.index')
                               ->with('success', 'Счет успешно удален');
            } else {
                return redirect()->back()->with('error', 'Ошибка при удалении счета');
            }

        } catch (\Exception $e) {
            Log::error('Ошибка удаления счета', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Ошибка удаления счета: ' . $e->getMessage());
        }
    }
}
