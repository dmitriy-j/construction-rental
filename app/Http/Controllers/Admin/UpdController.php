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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdController extends Controller
{
    protected $updProcessingService;

    public function __construct(UpdProcessingService $updProcessingService)
    {
        $this->updProcessingService = $updProcessingService;
    }

    public function index(Request $request)
    {
        $query = Upd::with(['order', 'lessorCompany', 'lesseeCompany'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $upds = $query->paginate(20);
        $documents = $upds; // Создаем переменную $documents для совместимости

        $statuses = [
            'all' => 'Все статусы',
            Upd::STATUS_PENDING => 'Ожидающие',
            Upd::STATUS_ACCEPTED => 'Принятые',
            Upd::STATUS_PROCESSED => 'Проведенные',
            Upd::STATUS_REJECTED => 'Отклоненные',
        ];

        $stats = [
            'contracts' => \App\Models\Contract::count(),
            'delivery_notes' => \App\Models\DeliveryNote::count(),
            'waybills' => \App\Models\Waybill::count(),
            'completion_acts' => \App\Models\CompletionAct::count(),
            'upds' => Upd::count(),
            'invoices' => \App\Models\Invoice::count(),
        ];

        $type = 'upds';

        return view('admin.documents.index', compact('documents', 'upds', 'statuses', 'stats', 'type'));
    }

    public function show(Upd $upd)
    {
        $upd->load([
            'order' => function($query) {
                $query->select('id', 'company_order_number', 'lessor_company_id', 'lessee_company_id');
            },
            'lessorCompany',
            'lesseeCompany',
            'items'
        ]);

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
            // Детальная проверка прав с логированием
            \Log::debug('Проверка прав пользователя', [
                'user_id' => auth()->id(),
                'user_roles' => auth()->user()->getRoleNames()->toArray(),
                'is_admin' => auth()->user()->isAdmin(),
                'upd_id' => $upd->id
            ]);

            // Обновленная проверка прав
            $allowedRoles = ['platform_super', 'platform_admin', 'financial_manager'];

            if (!auth()->check() || !auth()->user()->hasAnyRole($allowedRoles)) {
                \Log::warning('Доступ запрещен: недостаточно прав', [
                    'user_id' => auth()->id(),
                    'user_roles' => auth()->user()->getRoleNames()->toArray(),
                    'required_roles' => $allowedRoles
                ]);
                abort(403, 'Недостаточно прав для принятия УПД. Требуемые роли: ' . implode(', ', $allowedRoles));
            }

            // Проверка статуса УПД
            if ($upd->status !== Upd::STATUS_PENDING) {
                throw new \Exception('УПД уже был обработан. Текущий статус: ' . $upd->status);
            }

            DB::beginTransaction();

            // Логирование перед принятием
            \Log::info('Принятие УПД', [
                'upd_id' => $upd->id,
                'user_id' => auth()->id(),
                'current_status' => $upd->status,
                'type' => $upd->type
            ]);

            $upd->accept();

            DB::commit();

            \Log::info('УПД успешно принят', [
                'upd_id' => $upd->id,
                'new_status' => $upd->status,
                'accepted_at' => $upd->accepted_at
            ]);

            return redirect()->back()->with('success', 'УПД успешно принят и проведен.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ошибка принятия УПД', [
                'upd_id' => $upd->id,
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Ошибка принятия УПД: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Upd $upd)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500'
        ]);

        try {
            // Проверяем права доступа
            if (!auth()->user()->isAdmin()) {
                abort(403);
            }

            $upd->reject($request->reason); // Используем обновленный метод модели

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
