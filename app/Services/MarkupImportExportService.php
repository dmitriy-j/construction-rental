<?php

namespace App\Services;

use App\Models\PlatformMarkup;
use App\Models\Platform;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class MarkupImportExportService
{
    protected $markupCalculationService;

    public function __construct(MarkupCalculationService $markupCalculationService)
    {
        $this->markupCalculationService = $markupCalculationService;
    }

    /**
     * Экспорт наценок в CSV формат
     */
    public function exportToCsv(array $filters = []): string
    {
        $query = PlatformMarkup::with(['markupable', 'platform']);

        // Применяем фильтры
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (!empty($filters['markupable_type'])) {
            $query->where('markupable_type', $filters['markupable_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $markups = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->generateCsvContent($markups);
    }

    /**
     * Генерация CSV контента
     */
    private function generateCsvContent(Collection $markups): string
    {
        $output = fopen('php://temp', 'r+');

        // Заголовки CSV
        fputcsv($output, [
            'ID',
            'Платформа',
            'Тип сущности',
            'ID сущности',
            'Название сущности',
            'Контекст',
            'Тип наценки',
            'Тип расчета',
            'Значение',
            'Приоритет',
            'Статус',
            'Действует с',
            'Действует до',
            'Правила (JSON)',
            'Создана',
            'Обновлена'
        ], ';');

        // Данные
        foreach ($markups as $markup) {
            fputcsv($output, [
                $markup->id,
                $markup->platform->name ?? 'N/A',
                $this->getMarkupableTypeLabel($markup->markupable_type),
                $markup->markupable_id,
                $this->getMarkupableName($markup),
                $markup->entity_type,
                $markup->type,
                $markup->calculation_type,
                $markup->value,
                $markup->priority,
                $markup->is_active ? 'Активна' : 'Неактивна',
                $markup->valid_from ? $markup->valid_from->format('Y-m-d') : '',
                $markup->valid_to ? $markup->valid_to->format('Y-m-d') : '',
                json_encode($markup->rules, JSON_UNESCAPED_UNICODE),
                $markup->created_at->format('Y-m-d H:i:s'),
                $markup->updated_at->format('Y-m-d H:i:s')
            ], ';');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Импорт наценок из CSV
     */
    public function importFromCsv(string $content, bool $dryRun = true): array
    {
        $lines = explode("\n", trim($content));
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
            'dry_run' => $dryRun
        ];

        // Пропускаем заголовок
        array_shift($lines);

        DB::beginTransaction();

        try {
            foreach ($lines as $lineNumber => $line) {
                if (empty(trim($line))) continue;

                $data = str_getcsv($line, ';');

                if (count($data) < 10) {
                    $results['errors'][] = "Строка " . ($lineNumber + 2) . ": Неверный формат данных";
                    continue;
                }

                $result = $this->processImportRow($data, $lineNumber + 2, $dryRun);

                if (isset($result['error'])) {
                    $results['errors'][] = $result['error'];
                } else {
                    $results['processed']++;
                    if ($result['action'] === 'created') $results['created']++;
                    if ($result['action'] === 'updated') $results['updated']++;
                }
            }

            if ($dryRun || empty($results['errors'])) {
                DB::commit();
            } else {
                DB::rollBack();
                $results['errors'][] = "Импорт отменен из-за ошибок";
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Системная ошибка: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Обработка строки импорта
     */
    private function processImportRow(array $data, int $lineNumber, bool $dryRun): array
    {
        // Валидация данных
        $validation = $this->validateImportRow($data, $lineNumber);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }

        $validated = $validation['data'];

        // Поиск существующей наценки
        $existing = PlatformMarkup::where([
            'platform_id' => $validated['platform_id'],
            'entity_type' => $validated['entity_type'],
            'markupable_type' => $validated['markupable_type'],
            'markupable_id' => $validated['markupable_id'],
        ])->first();

        if ($existing) {
            // Обновление существующей
            if (!$dryRun) {
                $existing->update($validated);
                $existing->logAudit('updated', $existing->getOriginal(), $existing->toArray(), 'Импорт из CSV');
                $this->markupCalculationService->clearAffectedCache($existing);
            }
            return ['action' => 'updated'];
        } else {
            // Создание новой
            if (!$dryRun) {
                $markup = PlatformMarkup::create($validated);
                $markup->logAudit('created', null, $markup->toArray(), 'Импорт из CSV');
                $this->markupCalculationService->clearAffectedCache($markup);
            }
            return ['action' => 'created'];
        }
    }

    /**
     * Валидация строки импорта
     */
    private function validateImportRow(array $data, int $lineNumber): array
    {
        // Базовое преобразование данных
        $rowData = [
            'platform_name' => $data[1] ?? '',
            'markupable_type' => $this->parseMarkupableType($data[2] ?? ''),
            'markupable_id' => $data[3] ?? null,
            'entity_type' => $data[5] ?? '',
            'type' => $data[6] ?? '',
            'calculation_type' => $data[7] ?? 'addition',
            'value' => $data[8] ?? 0,
            'priority' => $data[9] ?? 0,
            'is_active' => strtolower($data[10] ?? '') === 'активна',
            'valid_from' => $this->parseDate($data[11] ?? ''),
            'valid_to' => $this->parseDate($data[12] ?? ''),
            'rules' => $this->parseRules($data[13] ?? '')
        ];

        // Валидация
        $validator = Validator::make($rowData, [
            'platform_name' => 'required|string',
            'markupable_type' => 'nullable|in:App\Models\Equipment,App\Models\EquipmentCategory,App\Models\Company',
            'markupable_id' => 'nullable|integer',
            'entity_type' => 'required|in:order,rental_request,proposal',
            'type' => 'required|in:fixed,percent,tiered,combined,seasonal',
            'calculation_type' => 'required|in:addition,multiplication,complex',
            'value' => 'required|numeric|min:0',
            'priority' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'rules' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'error' => "Строка {$lineNumber}: " . implode(', ', $validator->errors()->all())
            ];
        }

        // Проверка существования платформы
        $platform = Platform::where('name', $rowData['platform_name'])->first();
        if (!$platform) {
            return [
                'valid' => false,
                'error' => "Строка {$lineNumber}: Платформа '{$rowData['platform_name']}' не найдена"
            ];
        }

        // Проверка существования сущности
        if ($rowData['markupable_type'] && $rowData['markupable_id']) {
            $entityExists = $this->checkEntityExists($rowData['markupable_type'], $rowData['markupable_id']);
            if (!$entityExists) {
                return [
                    'valid' => false,
                    'error' => "Строка {$lineNumber}: Сущность {$rowData['markupable_type']}:{$rowData['markupable_id']} не найдена"
                ];
            }
        }

        return [
            'valid' => true,
            'data' => array_merge($rowData, ['platform_id' => $platform->id])
        ];
    }

    /**
     * Парсинг типа сущности
     */
    private function parseMarkupableType(string $type): ?string
    {
        $map = [
            'Оборудование' => Equipment::class,
            'Категория' => EquipmentCategory::class,
            'Компания' => Company::class,
            'Общая наценка' => null
        ];

        return $map[$type] ?? null;
    }

    /**
     * Парсинг даты
     */
    private function parseDate(?string $date): ?string
    {
        if (empty($date)) return null;

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Парсинг правил из JSON
     */
    private function parseRules(?string $rules): ?array
    {
        if (empty($rules)) return null;

        try {
            return json_decode($rules, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Проверка существования сущности
     */
    private function checkEntityExists(?string $type, ?int $id): bool
    {
        if (!$type || !$id) return true;

        return match($type) {
            Equipment::class => Equipment::where('id', $id)->exists(),
            EquipmentCategory::class => EquipmentCategory::where('id', $id)->exists(),
            Company::class => Company::where('id', $id)->exists(),
            default => false
        };
    }

    /**
     * Получение названия типа сущности
     */
    private function getMarkupableTypeLabel(?string $type): string
    {
        $map = [
            Equipment::class => 'Оборудование',
            EquipmentCategory::class => 'Категория',
            Company::class => 'Компания',
            null => 'Общая наценка'
        ];

        return $map[$type] ?? 'Неизвестно';
    }

    /**
     * Получение названия сущности
     */
    private function getMarkupableName(PlatformMarkup $markup): string
    {
        if (!$markup->markupable) return 'N/A';

        return match(get_class($markup->markupable)) {
            Equipment::class => $markup->markupable->title,
            EquipmentCategory::class => $markup->markupable->name,
            Company::class => $markup->markupable->legal_name,
            default => 'N/A'
        };
    }

    /**
     * Генерация шаблона для импорта
     */
    public function generateTemplate(): string
    {
        $output = fopen('php://temp', 'r+');

        fputcsv($output, [
            'ID (оставить пустым)',
            'Платформа (обязательно)',
            'Тип сущности (Оборудование/Категория/Компания/Общая наценка)',
            'ID сущности (если применимо)',
            'Название сущности (автозаполнение)',
            'Контекст (order/rental_request/proposal)',
            'Тип наценки (fixed/percent/tiered/combined/seasonal)',
            'Тип расчета (addition/multiplication/complex)',
            'Значение (обязательно)',
            'Приоритет (0-999)',
            'Статус (Активна/Неактивна)',
            'Действует с (YYYY-MM-DD)',
            'Действует до (YYYY-MM-DD)',
            'Правила (JSON)',
            'Создана (автозаполнение)',
            'Обновлена (автозаполнение)'
        ], ';');

        // Примеры данных
        fputcsv($output, [
            '',
            'Основная платформа',
            'Общая наценка',
            '',
            '',
            'order',
            'percent',
            'addition',
            '10.5',
            '0',
            'Активна',
            '2024-01-01',
            '2024-12-31',
            '{}',
            '',
            ''
        ], ';');

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }
}
