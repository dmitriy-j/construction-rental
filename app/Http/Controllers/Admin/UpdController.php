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
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentDataService;

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

    /**
     * Скачать файл УПД
     */
    public function download(Upd $upd)
    {
        try {
            Log::info('Попытка скачивания существующего файла УПД', [
                'upd_id' => $upd->id,
                'upd_number' => $upd->number,
                'file_path' => $upd->file_path
            ]);

            if (!$upd->file_path) {
                return redirect()->back()->with('error', 'Файл УПД не прикреплен');
            }

            // Для загруженных файлов (входящие УПД)
            if (Storage::disk('private')->exists($upd->file_path)) {
                $filename = "УПД_{$upd->number}.xlsx";
                return Storage::disk('private')->download($upd->file_path, $filename);
            }

            return redirect()->back()->with('error', 'Файл УПД не найден');

        } catch (\Exception $e) {
            Log::error('Ошибка скачивания существующего файла УПД', [
                'upd_id' => $upd->id,
                'error_message' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Ошибка при скачивании файла: ' . $e->getMessage());
        }
    }

    /**
     * Диагностика файловой системы для УПД  удалить!!!
     */
    public function diagnoseStorage(Upd $upd)
    {
        try {
            $diagnostics = [
                'upd_info' => [
                    'id' => $upd->id,
                    'number' => $upd->number,
                    'file_path' => $upd->file_path,
                    'file_path_type' => gettype($upd->file_path),
                ],
                'storage_check' => [
                    'private_exists' => $upd->file_path ? Storage::disk('private')->exists($upd->file_path) : false,
                    'public_exists' => $upd->file_path ? Storage::disk('public')->exists($upd->file_path) : false,
                    'local_exists' => $upd->file_path ? Storage::exists($upd->file_path) : false,
                ],
                'file_system' => [
                    'private_upds' => Storage::disk('private')->exists('upds') ? Storage::disk('private')->allFiles('upds') : 'Directory not exists',
                    'private_temp' => Storage::disk('private')->exists('temp') ? Storage::disk('private')->allFiles('temp') : 'Directory not exists',
                    'public_upds' => Storage::disk('public')->exists('upds') ? Storage::disk('public')->allFiles('upds') : 'Directory not exists',
                ],
                'absolute_paths' => [
                    'storage_app' => $upd->file_path ? storage_path('app/' . $upd->file_path) : 'No path',
                    'storage_app_private' => $upd->file_path ? storage_path('app/private/' . $upd->file_path) : 'No path',
                    'storage_app_public' => $upd->file_path ? storage_path('app/public/' . $upd->file_path) : 'No path',
                ],
                'file_exists_check' => [
                    'storage_app' => $upd->file_path ? file_exists(storage_path('app/' . $upd->file_path)) : false,
                    'storage_app_private' => $upd->file_path ? file_exists(storage_path('app/private/' . $upd->file_path)) : false,
                    'storage_app_public' => $upd->file_path ? file_exists(storage_path('app/public/' . $upd->file_path)) : false,
                    'direct' => $upd->file_path ? file_exists($upd->file_path) : false,
                ]
            ];

            return response()->json($diagnostics);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }


    /**
     * Скачать сгенерированный УПД из шаблона
     */
    public function downloadGenerated(Upd $upd, DocumentGeneratorService $documentGenerator, DocumentDataService $documentDataService)
    {
        try {
            Log::info('Генерация УПД для скачивания', ['upd_id' => $upd->id]);

            // Получаем активный шаблон УПД
            $template = $this->findUpdTemplate();

            if (!$template) {
                Log::error('Шаблон УПД не найден в системе', ['upd_id' => $upd->id]);
                return redirect()->back()
                    ->with('error', 'Шаблон УПД не найден в системе. Пожалуйста, создайте шаблон типа "УПД" в админке.');
            }

            // Используем DocumentDataService для подготовки данных
            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            Log::debug('Данные для УПД подготовлены', [
                'upd_id' => $upd->id,
                'items_count' => count($data['items'] ?? [])
            ]);

            // Генерируем документ в память (без сохранения на диск)
            Log::info('Генерация УПД в память', ['upd_id' => $upd->id]);
            $fileContent = $documentGenerator->generateDocumentInMemory($template, $data);

            Log::info('УПД сгенерирован в память', ['upd_id' => $upd->id]);

            // Возвращаем файл для скачивания
            $filename = "УПД_{$upd->number}.xlsx";

            return response()->streamDownload(function () use ($fileContent) {
                echo $fileContent;
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка генерации УПД для скачивания', [
                'upd_id' => $upd->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Ошибка генерации УПД: ' . $e->getMessage());
        }
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

    public function generateFromTemplate(Upd $upd, DocumentGeneratorService $documentGenerator, DocumentDataService $documentDataService)
    {
        try {
            Log::info('Начало генерации УПД из шаблона', ['upd_id' => $upd->id]);

            // Получаем активный шаблон УПД
            $template = $this->findUpdTemplate();

            if (!$template) {
                Log::error('Шаблон УПД не найден в системе', ['upd_id' => $upd->id]);
                return redirect()->back()
                    ->with('error', 'Шаблон УПД не найден в системе. Пожалуйста, создайте шаблон типа "УПД" в админке.');
            }

            Log::info('Шаблон УПД найден', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'upd_id' => $upd->id
            ]);

            // Используем DocumentDataService для подготовки данных
            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            Log::debug('Данные для УПД подготовлены', [
                'upd_id' => $upd->id,
                'items_count' => count($data['items'] ?? [])
            ]);

            // Генерируем документ
            Log::info('Начало генерации документа УПД', ['upd_id' => $upd->id]);

            // Создаем уникальное имя файла
            $fileName = 'УПД_' . $upd->number . '_' . time() . '.xlsx';
            $filePath = 'upds/' . $fileName;

            // Генерируем и сразу сохраняем в постоянное место
            $fullPath = $documentGenerator->generateAndSaveDocument($template, $data, $filePath);

            Log::info('Документ УПД сгенерирован и сохранен', [
                'upd_id' => $upd->id,
                'file_path' => $filePath,
                'full_path' => $fullPath
            ]);

            // Обновляем путь к файлу в УПД
            $upd->update([
                'file_path' => $filePath,
            ]);

            Log::info('Путь к файлу обновлен в УПД', [
                'upd_id' => $upd->id,
                'file_path' => $filePath
            ]);

            // Скачиваем файл
            return Storage::disk('private')->download($filePath, "УПД_{$upd->number}.xlsx");

        } catch (\Exception $e) {
            Log::error('Ошибка генерации УПД из шаблона', [
                'upd_id' => $upd->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Ошибка генерации УПД: ' . $e->getMessage());
        }
    }

    /**
     * Поиск шаблона УПД с различными вариантами написания
     */
    protected function findUpdTemplate()
    {
        // Пробуем разные варианты написания типа УПД
        $possibleTypes = ['упд', 'УПД', 'upd', 'UPD', 'Универсальный передаточный документ'];

        foreach ($possibleTypes as $type) {
            $template = DocumentTemplate::where('type', $type)
                ->where('is_active', true)
                ->first();

            if ($template) {
                Log::info('Найден шаблон УПД по типу', [
                    'type' => $type,
                    'template_id' => $template->id,
                    'template_name' => $template->name
                ]);
                return $template;
            }
        }

        // Если не нашли по типу, пробуем найти любой активный шаблон
        $template = DocumentTemplate::where('is_active', true)->first();
        if ($template) {
            Log::warning('Используем первый активный шаблон (не УПД)', [
                'template_id' => $template->id,
                'template_type' => $template->type,
                'template_name' => $template->name
            ]);
            return $template;
        }

        return null;
    }


}
