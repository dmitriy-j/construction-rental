<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upd;
use App\Models\Contract;
use App\Models\DeliveryNote;
use App\Models\Waybill;
use App\Models\CompletionAct;
use App\Models\Invoice;
use App\Services\UpdProcessingService;
use Illuminate\Http\Request;

class UpdController extends Controller
{
    protected $updProcessingService;

    public function __construct(UpdProcessingService $updProcessingService)
    {
        $this->updProcessingService = $updProcessingService;
    }

    public function index()
    {
        $upds = Upd::with(['order', 'lessorCompany', 'lesseeCompany'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Возвращаем общее представление документов с типом 'upds'
        return view('admin.documents.index', [
            'documents' => $upds,
            'type' => 'upds',
            'stats' => [
                'contracts' => Contract::count(),
                'delivery_notes' => DeliveryNote::count(),
                'waybills' => Waybill::count(),
                'completion_acts' => CompletionAct::count(),
                'upds' => Upd::count(),
                'invoices' => Invoice::count(),
            ]
        ]);
    }

    public function show(Upd $upd)
    {
        $upd->load(['order', 'lessorCompany', 'lesseeCompany', 'items']); // Добавили загрузку items
        return view('admin.documents.upds.show', compact('upd'));
    }

    public function verifyPaper(Request $request, Upd $upd)
    {
        $request->validate([
            'paper_number' => 'required|string',
            'paper_issue_date' => 'required|date',
            'paper_total_amount' => 'required|numeric|min:0',
        ]);

        try {
            $isVerified = $this->updProcessingService->verifyPaperUpd($upd, [
                'number' => $request->paper_number,
                'issue_date' => $request->paper_issue_date,
                'total_amount' => $request->paper_total_amount,
            ]);

            if ($isVerified) {
                $this->updProcessingService->acceptUpd($upd);
                return redirect()->back()->with('success', 'УПД успешно проверен и принят.');
            } else {
                return redirect()->back()->with('error', 'Данные бумажного УПД не совпадают с электронным.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка проверки УПД: ' . $e->getMessage());
        }
    }

    public function accept(Upd $upd)
    {
        try {
            $this->updProcessingService->acceptUpd($upd);
            return redirect()->back()->with('success', 'УПД успешно принят.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка принятия УПД: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Upd $upd)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500'
        ]);

        try {
            $this->updProcessingService->rejectUpd($upd, $request->reason);
            return redirect()->back()->with('success', 'УПД отклонен.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка отклонения УПД: ' . $e->getMessage());
        }
    }

    public function destroy(Upd $upd)
    {
        try {
            // Удаляем файл УПД
            if ($upd->file_path && Storage::exists($upd->file_path)) {
                Storage::delete($upd->file_path);
            }

            $upd->delete();

            return redirect()->route('admin.upds.index')
                ->with('success', 'УПД успешно удален.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка удаления УПД: ' . $e->getMessage());
        }
    }
}
