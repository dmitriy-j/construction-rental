<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompletionAct;
use App\Models\Order;
use App\Models\Upd;
use App\Models\Waybill; // Добавьте эту строку
use App\Services\UpdProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdController extends Controller
{
    protected $updProcessingService;

    public function __construct(UpdProcessingService $updProcessingService)
    {
        $this->updProcessingService = $updProcessingService;
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Получаем параметры сортировки
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $upds = Upd::where('lessor_company_id', $companyId)
            ->with(['order', 'order.lesseeCompany', 'waybill.completionAct'])
            ->orderBy($sortField, $sortDirection)
            ->paginate(20);

        $platformCompany = Company::where('is_platform', true)->first();

        return view('lessor.upds.index', compact('upds', 'platformCompany', 'sortField', 'sortDirection'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;

        // Получаем завершенные путевые листы с актами выполненных работ
        $waybills = Waybill::where('perspective', 'lessor') // Явное условие вместо scope
            ->whereHas('order', function ($query) use ($companyId) {
                $query->where('lessor_company_id', $companyId)
                    ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_PENDING]);
            })
            ->where('status', 'completed')
            ->whereHas('completionAct', function ($query) {
                $query->where('perspective', 'lessor'); // Явное условие вместо scope
            })
            ->whereDoesntHave('upd')
            ->with(['order', 'order.lesseeCompany', 'completionAct'])
            ->get();

        return view('lessor.upds.create', compact('waybills'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'waybill_id' => 'required|exists:waybills,id',
            'upd_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $waybill = Waybill::with(['order', 'completionAct'])->findOrFail($request->waybill_id);
        $order = $waybill->order;
        $completionActExists = CompletionAct::where('waybill_id', $request->waybill_id)
            ->where('perspective', 'lessor')
            ->exists();

        if (! $completionActExists) {
            return redirect()->back()
                ->with('error', 'Для выбранного путевого листа отсутствует акт выполненных работ. Невозможно загрузить УПД.')
                ->withInput();
        }

        // Проверяем права доступа
        if ($order->lessor_company_id !== Auth::user()->company_id) {
            return redirect()->back()->with('error', 'Недостаточно прав для загрузки УПД для этого путевого листа.');
        }

        try {
            $upd = $this->updProcessingService->processUploadedUpd(
                $order,
                $request->file('upd_file'),
                [
                    'waybill_id' => $waybill->id, // Убедитесь, что передается waybill_id
                ]
            );

            return redirect()->route('lessor.upds.index')
                ->with('success', 'УПД успешно загружен и ожидает проверки.');

        } catch (\Exception $e) {
            Log::error('Ошибка загрузки УПД', [
                'user_id' => Auth::id(),
                'waybill_id' => $request->waybill_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Ошибка загрузки УПД: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show(Upd $upd)
    {
        // Проверяем права доступа
        if ($upd->lessor_company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $upd->load([
            'order',
            'order.lesseeCompany',
            'lessorCompany',
            'waybill',
            'waybill.completionAct',
            'items',
        ]);

        return view('lessor.upds.show', compact('upd'));
    }

    public function destroy(Upd $upd)
    {
        // Проверяем права доступа
        if ($upd->lessor_company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Можно удалять только УПД в статусе "черновик"
        if ($upd->status !== Upd::STATUS_PENDING) {
            return redirect()->back()
                ->with('error', 'Можно удалять только УПД в статусе "Ожидает проверки".');
        }

        try {
            // Удаляем файл УПД из приватного хранилища
            if ($upd->file_path && Storage::disk('private')->exists($upd->file_path)) {
                Storage::disk('private')->delete($upd->file_path);
            }

            // Отвязываем от путевого листа
            if ($upd->waybill) {
                $upd->waybill->upd_id = null;
                $upd->waybill->save();
            }

            // Удаляем позиции УПД
            $upd->items()->delete();

            // Удаляем сам УПД
            $upd->delete();

            return redirect()->route('lessor.upds.index')
                ->with('success', 'УПД успешно удален.');

        } catch (\Exception $e) {
            Log::error('Ошибка удаления УПД', [
                'upd_id' => $upd->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Ошибка удаления УПД: '.$e->getMessage());
        }
    }

    public function download(Upd $upd)
    {
        // Проверяем права доступа
        if ($upd->lessor_company_id !== Auth::user()->company_id) {
            abort(403);
        }

        if (! Storage::disk('private')->exists($upd->file_path)) {
            abort(404, 'Файл УПД не найден');
        }

        return Storage::disk('private')->download($upd->file_path, "УПД_{$upd->number}.xlsx");
    }
}
