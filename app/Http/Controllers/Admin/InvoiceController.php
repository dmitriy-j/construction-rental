<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Upd;
use App\Services\InvoiceGeneratorService;
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
        $invoice->load(['order', 'company', 'upd', 'items']);

        return view('admin.invoices.show', compact('invoice'));
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
            Log::info('Попытка скачивания счета', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'file_path' => $invoice->file_path,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email
            ]);

            if (!$invoice->file_path) {
                Log::warning('Файл счета не сгенерирован', ['invoice_id' => $invoice->id]);
                return redirect()->back()->with('error', 'Файл счета не сгенерирован');
            }

            // Проверяем различные варианты хранения файла
            $fileFound = false;
            $storageDisk = null;

            // Проверяем приватное хранилище
            if (Storage::disk('private')->exists($invoice->file_path)) {
                $fileFound = true;
                $storageDisk = 'private';
            }
            // Проверяем публичное хранилище
            elseif (Storage::disk('public')->exists($invoice->file_path)) {
                $fileFound = true;
                $storageDisk = 'public';
            }
            // Проверяем стандартное хранилище
            elseif (Storage::exists($invoice->file_path)) {
                $fileFound = true;
                $storageDisk = 'local';
            }
            // Проверяем полный путь
            elseif (file_exists(storage_path('app/' . $invoice->file_path))) {
                $fileFound = true;
                $storageDisk = 'absolute';
            }

            if ($fileFound) {
                Log::info('Файл счета найден', [
                    'invoice_id' => $invoice->id,
                    'file_path' => $invoice->file_path,
                    'storage_disk' => $storageDisk
                ]);

                $filename = "Счет_{$invoice->number}.pdf";

                switch ($storageDisk) {
                    case 'private':
                        return Storage::disk('private')->download($invoice->file_path, $filename);
                    case 'public':
                        return Storage::disk('public')->download($invoice->file_path, $filename);
                    case 'absolute':
                        return response()->download(storage_path('app/' . $invoice->file_path), $filename);
                    default:
                        return Storage::download($invoice->file_path, $filename);
                }
            }

            Log::error('Файл счета не найден ни в одном хранилище', [
                'invoice_id' => $invoice->id,
                'file_path' => $invoice->file_path,
                'private_exists' => Storage::disk('private')->exists($invoice->file_path),
                'public_exists' => Storage::disk('public')->exists($invoice->file_path),
                'local_exists' => Storage::exists($invoice->file_path),
                'absolute_exists' => file_exists(storage_path('app/' . $invoice->file_path))
            ]);

            return redirect()->back()->with('error', 'Файл счета не найден');

        } catch (\Exception $e) {
            Log::error('Ошибка скачивания счета', [
                'invoice_id' => $invoice->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Ошибка при скачивании счета: ' . $e->getMessage());
        }
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
