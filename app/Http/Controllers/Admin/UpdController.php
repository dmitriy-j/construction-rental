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
use PhpOffice\PhpSpreadsheet\IOFactory;

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
 * Диагностика плейсхолдеров в шаблоне удалить!!!
 */
public function debugPlaceholders(Upd $upd, DocumentGeneratorService $documentGenerator)
{
    try {
        $template = $this->findUpdTemplate();

        if (!$template) {
            return response()->json(['error' => 'Template not found']);
        }

        $debugInfo = $documentGenerator->debugAllPlaceholders($template);
        return response()->json($debugInfo);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
}
    /**
     * Скачать сгенерированный УПД из шаблона
     */
    public function downloadGenerated(Upd $upd, DocumentGeneratorService $documentGenerator, DocumentDataService $documentDataService)
    {
        try {
            // Получаем данные с УЛУЧШЕННЫМИ комбинированными полями
            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            // Логируем сформированные названия для отладки
            if (isset($data['items']) && !empty($data['items'])) {
                Log::info('Сформированные названия позиций УПД', [
                    'upd_id' => $upd->id,
                    'items' => array_column($data['items'], 'name')
                ]);
            }

            // Дальше обычная генерация...
            $template = $this->findUpdTemplate();
            $fileContent = $documentGenerator->generateDocumentInMemory($template, $data);

            return response()->streamDownload(function () use ($fileContent) {
                echo $fileContent;
            }, "УПД_{$upd->number}.xlsx");

        } catch (\Exception $e) {
            Log::error('Ошибка генерации УПД с комбинированными полями', [
                'upd_id' => $upd->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Ошибка генерации УПД: ' . $e->getMessage());
        }
    }

    public function regenerateNumber(Upd $upd, UpdProcessingService $updProcessingService)
    {
        try {
            $oldNumber = $upd->number;
            $newNumber = $updProcessingService->generateUpdNumber();

            $upd->update(['number' => $newNumber]);

            Log::info('Номер УПД перегенерирован', [
                'upd_id' => $upd->id,
                'old_number' => $oldNumber,
                'new_number' => $newNumber
            ]);

            return redirect()->back()->with('success', "Номер УПД изменен: {$oldNumber} → {$newNumber}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка перегенерации номера: ' . $e->getMessage());
        }
    }

    public function show(Upd $upd)
    {
        // Загрузка связей
        $upd->load([
            'order.items.equipment',
            'waybill.equipment',
            'lessorCompany',
            'lesseeCompany',
            'items',
            'completionAct'
        ]);

        $documentDataService = app(\App\Services\DocumentDataService::class);

        $equipmentData = $documentDataService->getEquipmentDataForDisplay($upd);

        $preparedItems = $upd->items->map(function ($item, $index) use ($documentDataService, $equipmentData, $upd) {
            $item->full_name = $documentDataService->generateItemNameForDisplay($item, $upd, $equipmentData, $index);
            return $item;
        });

        return view('admin.documents.upds.show', compact('upd', 'preparedItems', 'equipmentData'));
    }

    /**
     * Диагностика данных и шаблона для УПД
     */
    public function diagnoseTemplate(Upd $upd, DocumentGeneratorService $documentGenerator, DocumentDataService $documentDataService)
    {
        try {
            $template = $this->findUpdTemplate();

            if (!$template) {
                return response()->json(['error' => 'Template not found']);
            }

            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            $diagnostics = $documentGenerator->debugTemplateAndData($template, $data);

            return response()->json($diagnostics);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Проверка маппинга шаблона
     */
    public function checkMapping(Upd $upd, DocumentDataService $documentDataService)
    {
        try {
            $template = $this->findUpdTemplate();

            if (!$template) {
                return response()->json(['error' => 'Template not found']);
            }

            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            $mapping = $template->mapping ?? [];

            $mappingCheck = [];
            foreach ($mapping as $field => $cell) {
                $value = $this->getValueFromDataForCheck($field, $data);
                $mappingCheck[] = [
                    'field' => $field,
                    'cell' => $cell,
                    'value' => $value,
                    'has_value' => !empty($value)
                ];
            }

            return response()->json([
                'template_id' => $template->id,
                'template_name' => $template->name,
                'mapping_count' => count($mapping),
                'mapping_check' => $mappingCheck,
                'data_structure' => array_keys($data)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Проверка плейсхолдеров в шаблоне
     */
    public function checkTemplatePlaceholders(Upd $upd, DocumentGeneratorService $documentGenerator, DocumentDataService $documentDataService)
    {
        try {
            $template = $this->findUpdTemplate();

            if (!$template) {
                return response()->json(['error' => 'Template not found']);
            }

            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            $diagnostics = $documentGenerator->debugTemplateAndData($template, $data);

            return response()->json($diagnostics);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    protected function getValueFromDataForCheck(string $path, array $data)
    {
        $pathParts = explode('.', $path);
        $current = $data;

        foreach ($pathParts as $part) {
            if (is_array($current) && array_key_exists($part, $current)) {
                $current = $current[$part];
            } else {
                return null;
            }
        }

        return is_array($current) ? null : $current;
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

    /**
     * Полная диагностика шаблона и данных
     */
    public function fullDiagnostics(Upd $upd, DocumentGeneratorService $documentGenerator, DocumentDataService $documentDataService)
    {
        try {
            $template = $this->findUpdTemplate();

            if (!$template) {
                return response()->json(['error' => 'Template not found']);
            }

            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            // Диагностика шаблона
            $templatePath = Storage::disk('public')->path($template->file_path);
            $spreadsheet = IOFactory::load($templatePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Находим ВСЕ плейсхолдеры в шаблоне
            $allPlaceholders = $this->findAllPlaceholdersInTemplate($worksheet);

            // Проверяем соответствие данных
            $flatData = $documentGenerator->flattenArray($data);

            $diagnostics = [
                'template_info' => [
                    'path' => $templatePath,
                    'exists' => file_exists($templatePath),
                    'rows' => $worksheet->getHighestRow(),
                    'columns' => $worksheet->getHighestColumn(),
                ],
                'placeholders_found' => $allPlaceholders,
                'data_available' => [
                    'total_keys' => count($flatData),
                    'sample_keys' => array_slice(array_keys($flatData), 0, 30),
                ],
                'mapping_info' => [
                    'has_mapping' => !empty($template->mapping),
                    'mapping_fields' => array_keys($template->mapping ?? [])
                ]
            ];

            return response()->json($diagnostics);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Полная диагностика шаблона УПД
     */
    public function templateDiagnostics(Upd $upd, DocumentDataService $documentDataService)
    {
        try {
            $template = $this->findUpdTemplate();

            if (!$template) {
                return response()->json(['error' => 'Template not found']);
            }

            $data = $documentDataService->prepareDocumentData($upd, 'упд');

            // Проверяем существование файла шаблона
            $templatePath = Storage::disk('public')->path($template->file_path);

            if (!file_exists($templatePath)) {
                return response()->json(['error' => 'Template file not found: ' . $templatePath]);
            }

            // Создаем плоский массив данных для проверки
            $flatData = $this->flattenDataForDiagnostics($data);

            return response()->json([
                'template_info' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'file_path' => $template->file_path,
                    'file_exists' => file_exists($templatePath),
                    'mapping_fields' => array_keys($template->mapping ?? [])
                ],
                'data_info' => [
                    'total_keys' => count($flatData),
                    'available_keys' => array_keys($flatData),
                    'sample_data' => array_slice($flatData, 0, 20) // первые 20 элементов
                ],
                'suggested_placeholders' => [
                    'Основные данные УПД' => [
                        '{{upd.number}}',
                        '{{upd.date}}',
                        '{{upd.contract_number}}',
                        '{{upd.contract_date}}',
                        '{{upd.total_without_vat}}',
                        '{{upd.total_vat}}',
                        '{{upd.total_with_vat}}',
                        '{{upd.period}}',
                        '{{upd.service_description}}'
                    ],
                    'Продавец' => [
                        '{{seller.name}}',
                        '{{seller.legal_name}}',
                        '{{seller.address}}',
                        '{{seller.inn}}',
                        '{{seller.kpp}}',
                        '{{seller.inn_kpp}}',
                        '{{seller.bank_name}}',
                        '{{seller.bik}}',
                        '{{seller.account_number}}'
                    ],
                    'Покупатель' => [
                        '{{buyer.name}}',
                        '{{buyer.legal_name}}',
                        '{{buyer.address}}',
                        '{{buyer.inn}}',
                        '{{buyer.kpp}}',
                        '{{buyer.inn_kpp}}',
                        '{{buyer.bank_name}}',
                        '{{buyer.bik}}',
                        '{{buyer.account_number}}'
                    ],
                    'Табличная часть' => [
                        '{{items.#.code}}',
                        '{{items.#.name}}',
                        '{{items.#.unit}}',
                        '{{items.#.quantity}}',
                        '{{items.#.price}}',
                        '{{items.#.amount}}',
                        '{{items.#.vat_rate}}',
                        '{{items.#.vat_amount}}',
                        '{{items.#.total_with_vat}}'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Вспомогательный метод для преобразования данных в плоскую структуру
     */
    protected function flattenDataForDiagnostics(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenDataForDiagnostics($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }


    /**
     * Находит все плейсхолдеры в шаблоне
     */
    protected function findAllPlaceholdersInTemplate($worksheet): array
    {
        $placeholders = [];
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                try {
                    $cellCoordinate = $col . $row;
                    $cell = $worksheet->getCell($cellCoordinate);
                    $value = $cell->getValue();

                    if (is_string($value)) {
                        // Ищем плейсхолдеры в разных форматах
                        if (preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches)) {
                            foreach ($matches[1] as $placeholder) {
                                $placeholder = trim($placeholder);
                                $placeholders[] = [
                                    'cell' => $cellCoordinate,
                                    'placeholder' => $placeholder,
                                    'value' => $value
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $placeholders;
    }

    /**
 * Точная диагностика плейсхолдеров в шаблоне Excel
 */
public function exactPlaceholderDiagnostics(Upd $upd)
{
    try {
        $template = $this->findUpdTemplate();

        if (!$template) {
            return response()->json(['error' => 'Template not found']);
        }

        // Проверяем существование файла шаблона
        $templatePath = Storage::disk('public')->path($template->file_path);

        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file not found: ' . $templatePath]);
        }

        // Загружаем Excel файл
        $spreadsheet = IOFactory::load($templatePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Находим ВСЕ текстовые значения в документе
        $allTextValues = [];
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        Log::info('Scanning template for placeholders', [
            'rows' => $highestRow,
            'columns' => $highestColumn
        ]);

        // Сканируем все ячейки
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                try {
                    $cellCoordinate = $col . $row;
                    $cell = $worksheet->getCell($cellCoordinate);
                    $value = $cell->getValue();

                    if (is_string($value) && !empty(trim($value))) {
                        $allTextValues[] = [
                            'cell' => $cellCoordinate,
                            'value' => $value,
                            'row' => $row,
                            'col' => $col
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Анализируем текстовые значения на наличие плейсхолдеров
        $foundPlaceholders = [];
        $suspectedPlaceholders = [];

        foreach ($allTextValues as $textValue) {
            $cellValue = $textValue['value'];

            // Ищем плейсхолдеры в формате {{...}}
            if (preg_match_all('/\{\{([^}]+)\}\}/', $cellValue, $matches)) {
                foreach ($matches[1] as $placeholder) {
                    $placeholder = trim($placeholder);
                    $foundPlaceholders[] = [
                        'cell' => $textValue['cell'],
                        'placeholder' => $placeholder,
                        'full_value' => $cellValue,
                        'type' => 'standard'
                    ];
                }
            }

            // Ищем возможные плейсхолдеры в других форматах
            if (preg_match('/[A-Za-z]+\.[A-Za-z_]+/', $cellValue)) {
                // Если в тексте есть точки (как в upd.number) но без фигурных скобок
                $suspectedPlaceholders[] = [
                    'cell' => $textValue['cell'],
                    'value' => $cellValue,
                    'type' => 'suspected'
                ];
            }

            // Ищем тексты, которые могут быть плейсхолдерами без скобок
            $commonPlaceholderPatterns = [
                'upd', 'seller', 'buyer', 'items', 'number', 'date', 'contract',
                'amount', 'price', 'quantity', 'vat', 'total', 'name', 'address',
                'inn', 'kpp', 'bank', 'bik', 'account'
            ];

            foreach ($commonPlaceholderPatterns as $pattern) {
                if (stripos($cellValue, $pattern) !== false && strlen($cellValue) < 100) {
                    $suspectedPlaceholders[] = [
                        'cell' => $textValue['cell'],
                        'value' => $cellValue,
                        'pattern' => $pattern,
                        'type' => 'keyword_match'
                    ];
                    break;
                }
            }
        }

        return response()->json([
            'template_info' => [
                'id' => $template->id,
                'name' => $template->name,
                'file_path' => $template->file_path,
                'file_exists' => true,
                'dimensions' => "{$highestRow} rows, {$highestColumn} columns"
            ],
            'scan_results' => [
                'cells_scanned' => count($allTextValues),
                'standard_placeholders_found' => count($foundPlaceholders),
                'suspected_placeholders_found' => count($suspectedPlaceholders)
            ],
            'standard_placeholders' => $foundPlaceholders,
            'suspected_placeholders' => array_slice($suspectedPlaceholders, 0, 50), // первые 50
            'expected_placeholders' => [
                'Основные данные УПД' => [
                    '{{upd.number}}',
                    '{{upd.date}}',
                    '{{upd.contract_number}}',
                    '{{upd.contract_date}}',
                    '{{upd.total_without_vat}}',
                    '{{upd.total_vat}}',
                    '{{upd.total_with_vat}}'
                ],
                'Продавец' => [
                    '{{seller.name}}',
                    '{{seller.address}}',
                    '{{seller.inn_kpp}}'
                ],
                'Покупатель' => [
                    '{{buyer.legal_name}}',
                    '{{buyer.address}}',
                    '{{buyer.inn_kpp}}'
                ]
            ],
            'analysis' => [
                'has_standard_placeholders' => !empty($foundPlaceholders),
                'standard_placeholder_count' => count($foundPlaceholders),
                'recommendation' => empty($foundPlaceholders) ?
                    'В шаблоне не найдены плейсхолдеры в формате {{key.path}}. Необходимо добавить плейсхолдеры в указанном формате.' :
                    'Найдены плейсхолдеры. Проверьте соответствие с expected_placeholders.'
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
}


}
