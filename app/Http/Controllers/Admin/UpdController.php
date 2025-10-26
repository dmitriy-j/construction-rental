<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DocumentTemplate;
use App\Models\Upd;
use App\Services\DocumentGeneratorService;
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
            'order' => function ($query) {
                $query->select('id', 'company_order_number', 'lessor_company_id', 'lessee_company_id');
            },
            'lessorCompany',
            'lesseeCompany',
            'items',
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
            return redirect()->back()->with('error', 'Ошибка проверки УПД: '.$e->getMessage());
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
                'upd_id' => $upd->id,
            ]);

            // Обновленная проверка прав
            $allowedRoles = ['platform_super', 'platform_admin', 'financial_manager'];

            if (! auth()->check() || ! auth()->user()->hasAnyRole($allowedRoles)) {
                \Log::warning('Доступ запрещен: недостаточно прав', [
                    'user_id' => auth()->id(),
                    'user_roles' => auth()->user()->getRoleNames()->toArray(),
                    'required_roles' => $allowedRoles,
                ]);
                abort(403, 'Недостаточно прав для принятия УПД. Требуемые роли: '.implode(', ', $allowedRoles));
            }

            // Проверка статуса УПД
            if ($upd->status !== Upd::STATUS_PENDING) {
                throw new \Exception('УПД уже был обработан. Текущий статус: '.$upd->status);
            }

            DB::beginTransaction();

            // Логирование перед принятием
            \Log::info('Принятие УПД', [
                'upd_id' => $upd->id,
                'user_id' => auth()->id(),
                'current_status' => $upd->status,
                'type' => $upd->type,
            ]);

            $upd->accept();

            DB::commit();

            \Log::info('УПД успешно принят', [
                'upd_id' => $upd->id,
                'new_status' => $upd->status,
                'accepted_at' => $upd->accepted_at,
            ]);

            return redirect()->back()->with('success', 'УПД успешно принят и проведен.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ошибка принятия УПД', [
                'upd_id' => $upd->id,
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Ошибка принятия УПД: '.$e->getMessage());
        }
    }

    public function reject(Request $request, Upd $upd)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        try {
            // Проверяем права доступа
            if (! auth()->user()->isAdmin()) {
                abort(403);
            }

            $upd->reject($request->reason); // Используем обновленный метод модели

            return redirect()->back()->with('success', 'УПД отклонен.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка отклонения УПД: '.$e->getMessage());
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
                ->with('error', 'Ошибка удаления УПД: '.$e->getMessage());
        }
    }

    public function generateFromTemplate(Upd $upd, DocumentGeneratorService $documentGenerator)
    {
        try {
            Log::info('Начало генерации УПД из шаблона', ['upd_id' => $upd->id]);

            // Получаем активный шаблон УПД
            $template = DocumentTemplate::where('type', 'упд')
                ->where('is_active', true)
                ->firstOrFail();

            Log::info('Шаблон УПД найден', ['template_id' => $template->id, 'template_name' => $template->name]);

            // Подготавливаем данные для УПД
            $data = $this->prepareUpdData($upd);
            Log::debug('Данные для УПД подготовлены', ['data_keys' => array_keys($data)]);

            // Генерируем документ
            Log::info('Начало генерации документа');
            $filePath = $documentGenerator->generateDocument($template, $data);
            Log::info('Документ сгенерирован', ['file_path' => $filePath]);

            // Обновляем путь к файлу в УПД
            $relativePath = str_replace(storage_path('app/'), '', $filePath);
            $upd->update([
                'file_path' => $relativePath,
            ]);

            Log::info('Путь к файлу обновлен в УПД', ['new_file_path' => $relativePath]);

            return response()->download($filePath, "УПД_{$upd->number}.xlsx")
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Ошибка генерации УПД', [
                'upd_id' => $upd->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Ошибка генерации УПД: '.$e->getMessage());
        }
    }

    protected function prepareUpdData(Upd $upd)
    {
        $platformCompany = Company::where('is_platform', true)->first();
        $lesseeCompany = $upd->lesseeCompany;

        // Проверяем наличие банковских реквизитов
        if (empty($platformCompany->bank_name) || empty($platformCompany->bik) || empty($platformCompany->bank_account)) {
            throw new \Exception('Не заполнены банковские реквизиты платформы');
        }

        if (empty($lesseeCompany->bank_name) || empty($lesseeCompany->bik) || empty($lesseeCompany->bank_account)) {
            throw new \Exception('Не заполнены банковские реквизиты арендатора');
        }

        // Формируем строку периода
        $periodString = '';
        if ($upd->service_period_start && $upd->service_period_end) {
            $periodString = $upd->service_period_start->format('d.m.Y').' - '.$upd->service_period_end->format('d.m.Y');
        }

        return [
            // Данные УПД (для плейсхолдеров вида {{upd.number}})
            'upd' => [
                'number' => $upd->number,
                'date' => $upd->issue_date ? $upd->issue_date->format('d.m.Y') : '',
                'contract_number' => $upd->contract_number,
                'contract_date' => $upd->contract_date ? $upd->contract_date->format('d.m.Y') : '',
                'shipment_date' => $upd->service_period_start ? $upd->service_period_start->format('d.m.Y') : '',
                'total_without_vat' => $upd->amount,
                'total_vat' => $upd->tax_amount,
                'total_with_vat' => $upd->total_amount,
                'period' => $periodString,
            ],
            // Данные платформы (продавца)
            'platform' => [
                'name' => $platformCompany->legal_name,
                'legal_name' => $platformCompany->legal_name,
                'address' => $platformCompany->legal_address,
                'inn' => $platformCompany->inn,
                'kpp' => $platformCompany->kpp,
                'inn_kpp' => $platformCompany->inn.' / '.$platformCompany->kpp,
                'bank_name' => $platformCompany->bank_name,
                'bik' => $platformCompany->bik,
                'account_number' => $platformCompany->bank_account,
                'correspondent_account' => $platformCompany->correspondent_account,
            ],
            // Данные арендатора (покупателя)
            'lessee' => [
                'name' => $lesseeCompany->legal_name,
                'legal_name' => $lesseeCompany->legal_name,
                'address' => $lesseeCompany->legal_address,
                'inn' => $lesseeCompany->inn,
                'kpp' => $lesseeCompany->kpp,
                'inn_kpp' => $lesseeCompany->inn.' / '.$lesseeCompany->kpp,
                'bank_name' => $lesseeCompany->bank_name,
                'bik' => $lesseeCompany->bik,
                'account_number' => $lesseeCompany->bank_account,
                'correspondent_account' => $lesseeCompany->correspondent_account,
            ],
            // Прямые поля для плейсхолдеров без префикса
            'upd_number' => $upd->number,
            'upd_date' => $upd->issue_date ? $upd->issue_date->format('d.m.Y') : '',
            'contract_number' => $upd->contract_number,
            'contract_date' => $upd->contract_date ? $upd->contract_date->format('d.m.Y') : '',
            'shipment_date' => $upd->service_period_start ? $upd->service_period_start->format('d.m.Y') : '',
            'total_without_vat' => $upd->amount,
            'total_vat' => $upd->tax_amount,
            'total_with_vat' => $upd->total_amount,
            'platform_name' => $platformCompany->legal_name,
            'platform_inn' => $platformCompany->inn,
            'platform_kpp' => $platformCompany->kpp,
            'platform_inn_kpp' => $platformCompany->inn.' / '.$platformCompany->kpp,
            'platform_address' => $platformCompany->legal_address,
            'lessee_name' => $lesseeCompany->legal_name,
            'lessee_legal_name' => $lesseeCompany->legal_name,
            'lessee_inn' => $lesseeCompany->inn,
            'lessee_kpp' => $lesseeCompany->kpp,
            'lessee_inn_kpp' => $lesseeCompany->inn.' / '.$lesseeCompany->kpp,
            'lessee_address' => $lesseeCompany->legal_address,
            'period' => $periodString,
            // Табличная часть
            'items' => $upd->items->map(function ($item) {
                return [
                    'code' => $item->code,
                    'name' => $item->name,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'amount' => $item->amount,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $item->vat_amount,
                    'total_with_vat' => $item->amount + $item->vat_amount,
                ];
            })->toArray(),
        ];
    }
}
