<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class DocumentGeneratorService
{
    public function generateDocument(DocumentTemplate $template, array $data)
    {
        try {
            Log::info('Начало генерации документа', ['template_id' => $template->id]);

            // Загружаем шаблон
            $templatePath = Storage::disk('public')->path($template->file_path);
            Log::debug('Путь к файлу шаблона', ['path' => $templatePath]);

            if (! file_exists($templatePath)) {
                throw new \Exception('Файл шаблона не существует: '.$templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $worksheet = $spreadsheet->getActiveSheet();
            Log::info('Шаблон успешно загружен');

            // 1. Парсим маппинг и выполняем простую подстановку по координатам
            $mapping = $template->mapping ?? [];
            Log::debug('Настройки маппинга', ['mapping' => $mapping]);

            foreach ($mapping as $field => $cell) {
                // Обработка коллекций (например, items.#.name)
                if (str_contains($field, '.#.')) {
                    $this->processCollection($worksheet, $field, $cell, $data);
                    continue;
                }

                // Стандартная обработка одиночных полей
                $value = $this->getValueFromData($field, $data);
                Log::debug('Подстановка значения в ячейку', [
                    'field' => $field,
                    'cell' => $cell,
                    'value' => $value,
                ]);

                // Если значение найдено, производим замену
                if ($value !== '') {
                    $worksheet->setCellValue($cell, $value);
                }
            }

            // 2. Дополнительный проход: ищем и заменяем плейсхолдеры вида {{field.path}} во всех ячейках
            $this->replacePlaceholdersInWorksheet($worksheet, $data);

            // Сохраняем временный файл
            $fileName = 'document_'.time().'.xlsx';
            $tempPath = storage_path('app/temp/'.$fileName);

            if (! file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempPath);

            Log::info('Документ успешно сохранен', ['path' => $tempPath]);

            return $tempPath;

        } catch (\Exception $e) {
            Log::error('Ошибка в сервисе генерации документов', [
                'template_id' => $template->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Генерация документа с автоматическим определением ячеек для табличной части
     */
    public function generateDocumentWithAutoMapping(DocumentTemplate $template, array $data)
    {
        try {
            Log::info('Генерация документа с автоматическим маппингом В ПАМЯТИ', [
                'template_id' => $template->id,
                'data_keys' => array_keys($data)
            ]);

            $filePath = $template->file_path;
            $fullPath = Storage::disk('public')->path($filePath);

            if (!file_exists($fullPath)) {
                throw new \Exception("Файл шаблона не найден: {$fullPath}");
            }

            // Загружаем Excel файл
            $spreadsheet = IOFactory::load($fullPath);

            // Обрабатываем все листы
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $this->processSheetWithAutoMapping($sheet, $data);
            }

            // Сохраняем В ПАМЯТЬ
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            Log::info('Документ сгенерирован с автоматическим маппингом В ПАМЯТИ', [
                'content_size' => strlen($content)
            ]);

            return $content;

        } catch (\Exception $e) {
            Log::error('Ошибка генерации документа с автоматическим маппингом', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обработка листа с автоматическим поиском и заменой плейсхолдеров
     */
    protected function processSheetWithAutoMapping($sheet, array $data)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        Log::debug('Обработка листа с автоматическим маппингом', [
            'rows' => $highestRow,
            'columns' => $highestColumn,
            'available_data' => array_keys($data)
        ]);

        // Создаем плоский массив для замены
        $flatData = $this->flattenArray($data);
        Log::debug('Плоские данные для замены', [
            'flat_data_keys' => array_keys($flatData),
            'sample_data' => array_slice($flatData, 0, 5) // первые 5 элементов для примера
        ]);

        // Сканируем все ячейки на листе
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellCoordinate = $col . $row;
                $cell = $sheet->getCell($cellCoordinate);
                $originalValue = $cell->getValue();

                if (is_string($originalValue)) {
                    $newValue = $originalValue;

                    // Заменяем все плейсхолдеры в строке
                    foreach ($flatData as $key => $replacement) {
                        $placeholder = '{{' . $key . '}}';
                        if (strpos($newValue, $placeholder) !== false) {
                            $formattedReplacement = $this->formatValue($replacement, $key);
                            $newValue = str_replace($placeholder, $formattedReplacement, $newValue);
                            Log::debug('Замена плейсхолдера', [
                                'cell' => $cellCoordinate,
                                'placeholder' => $placeholder,
                                'replacement' => $formattedReplacement,
                                'old_value' => $originalValue,
                                'new_value' => $newValue
                            ]);
                        }
                    }

                    if ($newValue !== $originalValue) {
                        $cell->setValue($newValue);
                    }
                }
            }
        }
    }

   /**
     * Преобразование многомерного массива в плоский - С УЧЕТОМ ТАБЛИЧНОЙ ЧАСТИ
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                // Особый случай: items - создаем плейсхолдеры для каждого поля
                if ($key === 'items' && !empty($value) && isset($value[0]) && is_array($value[0])) {
                    // Для табличной части создаем плейсхолдеры items.#.field
                    foreach ($value[0] as $field => $fieldValue) {
                        $tablePlaceholder = $newKey . '.#.' . $field;
                        $result[$tablePlaceholder] = ''; // Значение будет заполнено в табличной части
                    }

                    // Также добавляем обычные поля items для обратной совместимости
                    $result = array_merge($result, $this->flattenArray($value, $newKey));
                } else {
                    // Рекурсивно обрабатываем вложенные массивы
                    $result = array_merge($result, $this->flattenArray($value, $newKey));
                }
            } else {
                // Просто добавляем значение
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Проверяет, является ли массив коллекцией элементов
     */
    protected function isCollection(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        // Проверяем первый элемент - если у него есть числовые ключи, это коллекция
        $firstKey = array_key_first($array);
        return is_numeric($firstKey);
    }

    /**
     * Автоматическое определение и заполнение табличной части
     */
    protected function processTableItems($worksheet, array $data)
    {
        try {
            Log::info('=== AUTOMATIC TABLE PROCESSING START ===');

            $items = $data['items'] ?? [];

            if (empty($items)) {
                Log::warning('Табличная часть items пуста');
                return;
            }

            Log::info('Items found for table processing', [
                'items_count' => count($items),
                'first_item' => $items[0] ?? []
            ]);

            // 1. НАХОДИМ ВСЕ плейсхолдеры табличной части
            $tableStructure = $this->findAllTablePlaceholders($worksheet);

            if (empty($tableStructure['table_placeholders'])) {
                Log::warning('Не найдены плейсхолдеры табличной части', [
                    'all_placeholders_found' => array_column($tableStructure['all_placeholders'] ?? [], 'placeholder')
                ]);
                return;
            }

            // 2. ЗАПОЛНЯЕМ таблицу данными
            $this->fillDynamicTable($worksheet, $tableStructure, $items);

            Log::info('=== AUTOMATIC TABLE PROCESSING COMPLETE ===');

        } catch (\Exception $e) {
            Log::error('Ошибка обработки табличной части', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * НАХОДИТ ВСЕ плейсхолдеры в шаблоне без ограничений
     */
    protected function findAllTablePlaceholders($worksheet): array
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        $placeholders = [];
        $templateRow = null;

        Log::debug('Searching for ALL placeholders in worksheet', [
            'rows' => $highestRow,
            'columns' => $highestColumn
        ]);

        // Проходим по ВСЕМ ячейкам листа
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                try {
                    $cell = $worksheet->getCell($col . $row);
                    $value = $cell->getValue();

                    if (is_string($value)) {
                        // Ищем ЛЮБЫЕ плейсхолдеры, не только items.#
                        if (preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches)) {
                            foreach ($matches[1] as $placeholder) {
                                $placeholders[] = [
                                    'column' => $col,
                                    'row' => $row,
                                    'cell' => $col . $row,
                                    'placeholder' => $placeholder,
                                    'value' => $value
                                ];

                                Log::debug('Found ANY placeholder', [
                                    'placeholder' => $placeholder,
                                    'cell' => $col . $row,
                                    'value' => $value
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Пропускаем ошибки чтения ячеек
                    continue;
                }
            }
        }

        // Фильтруем только табличные плейсхолдеры
        $tablePlaceholders = array_filter($placeholders, function($item) {
            return strpos($item['placeholder'], 'items.') !== false;
        });

        $result = [
            'all_placeholders' => $placeholders,
            'table_placeholders' => $tablePlaceholders,
            'template_row' => $tablePlaceholders[0]['row'] ?? null,
            'found_count' => count($tablePlaceholders)
        ];

        Log::info('All placeholders analysis', [
            'total_placeholders' => count($placeholders),
            'table_placeholders_count' => count($tablePlaceholders),
            'table_placeholders' => array_column($tablePlaceholders, 'placeholder')
        ]);

        return $result;
    }

    /**
     * ЗАПОЛНЯЕТ динамическую таблицу данными
     */
    protected function fillDynamicTable($worksheet, array $tableStructure, array $items)
    {
        $tablePlaceholders = $tableStructure['table_placeholders'];
        $templateRow = $tableStructure['template_row'];

        if (empty($tablePlaceholders) || $templateRow === null) {
            Log::warning('No table structure found for filling');
            return;
        }

        Log::info('Filling dynamic table', [
            'template_row' => $templateRow,
            'items_count' => count($items),
            'all_table_placeholders' => array_column($tablePlaceholders, 'placeholder')
        ]);

        // Группируем плейсхолдеры по строкам (на случай, если они в разных строках)
        $placeholdersByRow = [];
        foreach ($tablePlaceholders as $placeholderInfo) {
            $row = $placeholderInfo['row'];
            if (!isset($placeholdersByRow[$row])) {
                $placeholdersByRow[$row] = [];
            }
            $placeholdersByRow[$row][] = $placeholderInfo;
        }

        // Используем первую строку с табличными плейсхолдерами как шаблон
        $templateRow = min(array_keys($placeholdersByRow));
        $templatePlaceholders = $placeholdersByRow[$templateRow];

        Log::info('Using template row', [
            'row' => $templateRow,
            'placeholders_in_row' => array_column($templatePlaceholders, 'placeholder')
        ]);

        // Если элементов больше 1, вставляем дополнительные строки
        if (count($items) > 1) {
            $worksheet->insertNewRowBefore($templateRow + 1, count($items) - 1);
            Log::info('Inserted additional rows', ['count' => count($items) - 1]);
        }

        // ЗАПОЛНЯЕМ КАЖДУЮ СТРОКУ ДАННЫМИ
        foreach ($items as $index => $item) {
            $currentRow = $templateRow + $index;

            Log::debug('Filling table row with data', [
                'row' => $currentRow,
                'item_index' => $index
            ]);

            // Для каждого плейсхолдера в шаблонной строке находим поле и заполняем
            foreach ($templatePlaceholders as $placeholderInfo) {
                $placeholder = $placeholderInfo['placeholder'];
                $column = $placeholderInfo['column'];

                // Определяем имя поля из плейсхолдера
                $fieldName = $this->extractFieldNameFromPlaceholder($placeholder);

                if (isset($item[$fieldName])) {
                    $value = $this->formatTableValue($item[$fieldName], $fieldName);
                    $cellAddress = $column . $currentRow;

                    $worksheet->setCellValue($cellAddress, $value);

                    Log::debug('Table cell filled', [
                        'cell' => $cellAddress,
                        'placeholder' => $placeholder,
                        'field' => $fieldName,
                        'value' => $value
                    ]);
                } else {
                    Log::warning('Field not found in item', [
                        'field' => $fieldName,
                        'placeholder' => $placeholder,
                        'available_fields' => array_keys($item)
                    ]);
                }
            }
        }

        Log::info('Dynamic table filled successfully', [
            'rows_filled' => count($items),
            'start_row' => $templateRow,
            'end_row' => $templateRow + count($items) - 1
        ]);
    }

    /**
     * Извлекает имя поля из плейсхолдера
     */
    protected function extractFieldNameFromPlaceholder(string $placeholder): string
    {
        // Обрабатываем разные форматы:
        // items.#.name -> name
        // items.0.name -> name
        // items.name -> name
        if (preg_match('/items\.(#|\d+)\.(.+)/', $placeholder, $matches)) {
            return $matches[2];
        }
        if (preg_match('/items\.(.+)/', $placeholder, $matches)) {
            return $matches[1];
        }

        return $placeholder;
    }

    /**
     * Находит шаблонную строку с плейсхолдерами items.# в WORKSHEET
     */
    protected function findTemplateRowInWorksheet($worksheet): ?array
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cell = $worksheet->getCell($col . $row);
                $value = $cell->getValue();

                if (is_string($value) && preg_match('/items\.#\./', $value)) {
                    Log::debug('Found template row with items.# placeholder', [
                        'cell' => $col . $row,
                        'value' => $value
                    ]);

                    // Собираем информацию о всех плейсхолдерах в этой строке
                    $placeholders = $this->extractPlaceholdersFromRow($worksheet, $row);

                    return [
                        'row_number' => $row,
                        'placeholders' => $placeholders,
                        'sample_cell' => $col . $row,
                        'sample_value' => $value
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Находит номер строки-шаблона из MAPPING массива
     */
    protected function findTemplateRowFromMapping(array $mapping): ?int
    {
        foreach ($mapping as $cellCoordinate) {
            if (preg_match('/^([A-Z]+)(\d+)$/', $cellCoordinate, $matches)) {
                return (int)$matches[2];
            }
        }
        return null;
    }

    /**
     * Извлекает все плейсхолдеры из строки
     */
    protected function extractPlaceholdersFromRow($worksheet, int $row): array
    {
        $placeholders = [];
        $highestColumn = $worksheet->getHighestColumn();

        Log::debug('Searching for placeholders in row', ['row' => $row, 'highest_column' => $highestColumn]);

        for ($col = 'A'; $col <= $highestColumn; $col++) {
            try {
                $cell = $worksheet->getCell($col . $row);
                $value = $cell->getValue();

                if (is_string($value)) {
                    Log::debug('Checking cell for placeholders', [
                        'cell' => $col . $row,
                        'value' => $value
                    ]);

                    if (preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches)) {
                        foreach ($matches[1] as $placeholder) {
                            if (strpos($placeholder, 'items.#.') !== false) {
                                $placeholders[$col] = $placeholder;
                                Log::debug('Found table placeholder', [
                                    'cell' => $col . $row,
                                    'placeholder' => $placeholder
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error reading cell', ['cell' => $col . $row, 'error' => $e->getMessage()]);
            }
        }

        Log::debug('Placeholders found in row', [
            'row' => $row,
            'placeholders' => $placeholders,
            'count' => count($placeholders)
        ]);

        return $placeholders;
    }

    /**
     * Заполнение табличной части через маппинг из шаблона
     */
    protected function fillTableWithTemplateMapping($worksheet, DocumentTemplate $template, array $items, array $data = []): void
    {
        try {
            Log::info('=== ЗАПОЛНЕНИЕ ТАБЛИЦЫ ЧЕРЕЗ МАППИНГ ШАБЛОНА ===', [
                'template_id' => $template->id,
                'items_count' => count($items),
                'mapping_keys' => array_keys($template->mapping ?? [])
            ]);

            // ДОБАВИТЬ ПРОВЕРКУ СТРУКТУРЫ ITEMS
            Log::debug('Структура данных items:', [
                'items_count' => count($items),
                'first_item_keys' => !empty($items) ? array_keys($items[0]) : [],
                'first_item_data' => !empty($items) ? $items[0] : []
            ]);

            $mapping = $template->mapping ?? [];

            if (empty($mapping)) {
                Log::warning('Маппинг шаблона пуст');
                return;
            }

            // Группируем маппинг по строкам для табличной части
            $tableMapping = $this->extractTableMapping($mapping, $items);

            // ДОБАВИТЬ ПРОВЕРКУ
            if (empty($tableMapping['columns']) || $tableMapping['start_row'] === null) {
                Log::error('Не удалось извлечь табличный маппинг', [
                    'tableMapping' => $tableMapping,
                    'available_mapping_fields' => array_keys($mapping),
                    'mapping' => $mapping
                ]);

                // ВРЕМЕННО: попробуем альтернативный метод
                $this->tryAlternativeTableFilling($worksheet, $mapping, $items);
                return;
            }

            Log::info('Найден маппинг табличной части', [
                'start_row' => $tableMapping['start_row'],
                'columns_mapping' => $tableMapping['columns']
            ]);

            // Вставляем дополнительные строки если нужно
            if (count($items) > 1) {
                $worksheet->insertNewRowBefore($tableMapping['start_row'] + 1, count($items) - 1);
            }

            // Заполняем таблицу
            $this->fillTableRows($worksheet, $tableMapping, $items);

            Log::info('=== ТАБЛИЦА УСПЕШНО ЗАПОЛНЕНА ===');

        } catch (\Exception $e) {
            Log::error('Ошибка заполнения таблицы через маппинг шаблона', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Автоматическая замена всех плейсхолдеров в документе
     */
    protected function replaceAllPlaceholders($worksheet, array $data)
    {
        $replacedCount = 0;
        $processedCells = 0;

        try {
            Log::info('=== ULTRA RELIABLE PLACEHOLDER REPLACEMENT START ===');

            // Создаем плоский массив данных БЕЗ табличных плейсхолдеров
            $flatData = $this->flattenArrayForPlaceholders($data);

            Log::info('Flat data for placeholders', [
                'total_keys' => count($flatData),
                'keys_sample' => array_slice(array_keys($flatData), 0, 20)
            ]);

            // Используем итератор для ВСЕХ ячеек (включая пустые)
            $iterator = $worksheet->getRowIterator();

            foreach ($iterator as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // ВАЖНО: включаем пустые ячейки

                foreach ($cellIterator as $cell) {
                    $processedCells++;
                    $cellCoordinate = $cell->getCoordinate();

                    try {
                        $originalValue = $cell->getValue();

                        // Обрабатываем только строковые значения
                        if (!is_string($originalValue) || empty(trim($originalValue))) {
                            continue;
                        }

                        $newValue = $originalValue;
                        $hasChanges = false;

                        // Ищем плейсхолдеры в формате {{field.path}}
                        if (preg_match_all('/\{\{\s*([^}]+?)\s*\}\}/', $originalValue, $matches)) {
                            foreach ($matches[1] as $index => $placeholder) {
                                $placeholder = trim($placeholder);
                                $fullMatch = $matches[0][$index];

                                // НЕ пропускаем табличные плейсхолдеры - заменяем ВСЕ
                                // Ищем значение в данных
                                $value = $flatData[$placeholder] ?? null;

                                if ($value !== null && $value !== '') {
                                    $formattedValue = $this->formatValue($value, $placeholder);
                                    $newValue = str_replace($fullMatch, $formattedValue, $newValue);
                                    $replacedCount++;
                                    $hasChanges = true;

                                    Log::debug('SUCCESS: Placeholder replaced', [
                                        'cell' => $cellCoordinate,
                                        'placeholder' => $placeholder,
                                        'value' => $formattedValue,
                                        'original' => $originalValue,
                                        'new' => $newValue
                                    ]);
                                } else {
                                    Log::debug('MISSING: Placeholder not found in data', [
                                        'cell' => $cellCoordinate,
                                        'placeholder' => $placeholder,
                                        'full_match' => $fullMatch,
                                        'in_flat_data' => array_key_exists($placeholder, $flatData)
                                    ]);
                                }
                            }
                        }

                        // Если были изменения - обновляем ячейку
                        if ($hasChanges) {
                            $cell->setValue($newValue);
                        }

                    } catch (\Exception $e) {
                        Log::warning('Error processing cell', [
                            'cell' => $cellCoordinate,
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }
            }

            Log::info('=== ULTRA RELIABLE PLACEHOLDER REPLACEMENT COMPLETE ===', [
                'processed_cells' => $processedCells,
                'replacements_made' => $replacedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ultra reliable placeholder replacement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Упрощенный flattenArray только для плейсхолдеров (без табличной части)
     */
    protected function flattenArrayForPlaceholders(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                // Рекурсивно обрабатываем вложенные массивы, но пропускаем items
                if ($key !== 'items') {
                    $result = array_merge($result, $this->flattenArrayForPlaceholders($value, $newKey));
                }
            } else {
                // Просто добавляем значение
                $result[$newKey] = $value;
            }
        }

        return $result;
    }


    /**
     * Замена плейсхолдеров в значении ячейки
     */
    protected function replacePlaceholdersInCell(string $cellValue, array $data): string
    {
        // ВАЖНО: Полностью пропускаем плейсхолдер QR-кода - он обрабатывается отдельно как изображение
        if (strpos($cellValue, '{{payment_qr_code}}') !== false) {
            return ''; // Полностью очищаем ячейку от любых данных
        }

        // Обрабатываем остальные плейсхолдеры
        $newValue = $cellValue;

        if (preg_match_all('/\{\{([^}]+)\}\}/', $cellValue, $matches)) {
            foreach ($matches[1] as $placeholder) {
                // Пропускаем QR-код
                if ($placeholder === 'payment_qr_code') {
                    continue;
                }

                $value = $this->getValueByPath($data, trim($placeholder));
                if ($value !== null && $value !== '') {
                    $formattedValue = $this->formatValue($value, $placeholder);
                    $newValue = str_replace("{{{$placeholder}}}", $formattedValue, $newValue);
                }
            }
        }

        return $newValue;
    }
        /**
     * Обработка табличной части с динамическим добавлением строк
     */
    protected function processTableSection($worksheet, array $mapping, array $data): void
    {
        try {
            Log::info('Processing table section with mapping', [
                'mapping_keys_count' => count($mapping),
                'has_items' => isset($data['items']),
                'items_count' => isset($data['items']) ? count($data['items']) : 0
            ]);

            // Получаем данные для табличной части
            $items = $data['items'] ?? [];

            if (empty($items)) {
                Log::warning('No items found for table processing');
                return;
            }

            // Извлекаем табличный маппинг
            $tableMapping = $this->extractTableMapping($mapping, $items);

            if (empty($tableMapping['columns']) || $tableMapping['start_row'] === null) {
                Log::warning('No table mapping found, trying automatic table processing');
                $this->processTableItems($worksheet, $data);
                return;
            }

            Log::info('Table mapping found', [
                'start_row' => $tableMapping['start_row'],
                'columns_count' => count($tableMapping['columns']),
                'items_count' => count($items)
            ]);

            // Вставляем дополнительные строки если нужно
            if (count($items) > 1) {
                $worksheet->insertNewRowBefore($tableMapping['start_row'] + 1, count($items) - 1);
                Log::info('Inserted additional rows for table', ['count' => count($items) - 1]);
            }

            // Заполняем таблицу данными
            $this->fillTableWithMapping($worksheet, $tableMapping, $items);

            Log::info('Table section processing completed', [
                'items_processed' => count($items)
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing table section with mapping', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Пробуем автоматический метод как запасной вариант
            try {
                $this->processTableItems($worksheet, $data);
            } catch (\Exception $e2) {
                Log::error('Automatic table processing also failed', [
                    'error' => $e2->getMessage()
                ]);
            }
        }
    }

    /**
     * Заполнение таблицы через маппинг
     */
    protected function fillTableWithMapping($worksheet, array $tableMapping, array $items): void
    {
        $startRow = $tableMapping['start_row'];
        $columns = $tableMapping['columns'];

        Log::info('Filling table with mapping', [
            'start_row' => $startRow,
            'columns' => $columns,
            'items_count' => count($items)
        ]);

        foreach ($items as $index => $item) {
            $currentRow = $startRow + $index;

            Log::debug('Filling table row', [
                'row' => $currentRow,
                'item_index' => $index,
                'item_fields' => array_keys($item)
            ]);

            foreach ($columns as $column => $fieldName) {
                $value = $item[$fieldName] ?? '';

                if ($value !== '') {
                    $formattedValue = $this->formatTableValue($value, $fieldName);
                    $cellCoordinate = $column . $currentRow;

                    $worksheet->setCellValue($cellCoordinate, $formattedValue);

                    Log::debug('Table cell filled with mapping', [
                        'cell' => $cellCoordinate,
                        'field' => $fieldName,
                        'value' => $formattedValue
                    ]);
                }
            }
        }
    }

    protected function placeTotalAmounts($worksheet, array $tableMapping, array $items, array $data = [])
    {
        // Используем переданные данные
        $totalWithoutVat = $data['upd']['total_without_vat'] ?? 0;
        $totalVat = $data['upd']['total_vat'] ?? 0;
        $totalWithVat = $data['upd']['total_with_vat'] ?? 0;

        $startRow = $tableMapping['start_row'];
        $itemsCount = count($items);

        // Вычисляем строку для итогов (после последней строки таблицы + отступ)
        $totalRow = $startRow + $itemsCount + 1;

        // Размещаем итоги в нужных ячейках (настройте колонки под ваш шаблон)
        $worksheet->setCellValue('BC' . $totalRow, $this->formatTableValue($totalWithoutVat, 'amount'));
        $worksheet->setCellValue('BC' . ($totalRow + 1), $this->formatTableValue($totalVat, 'amount'));
        $worksheet->setCellValue('BC' . ($totalRow + 2), $this->formatTableValue($totalWithVat, 'amount'));

        Log::info('Итоговые суммы размещены', [
            'total_row' => $totalRow,
            'total_without_vat' => $totalWithoutVat,
            'total_vat' => $totalVat,
            'total_with_vat' => $totalWithVat,
            'items_count' => $itemsCount,
            'start_row' => $startRow
        ]);
    }

    /**
     * Извлекает маппинг для табличной части из общего маппинга
     */
    protected function extractTableMapping(array $mapping, array $items): array
    {
        $tableMapping = [
            'start_row' => null,
            'columns' => []
        ];

        // ДОБАВИТЬ ПОДРОБНОЕ ЛОГИРОВАНИЕ
        Log::debug('=== НАЧАЛО extractTableMapping ===');
        Log::debug('Входные данные mapping:', ['mapping_keys' => array_keys($mapping)]);
        Log::debug('Полный mapping:', $mapping);

        foreach ($mapping as $field => $cell) {
            Log::debug('Обработка поля:', ['field' => $field, 'cell' => $cell]);

            // Обрабатываем маппинги для табличной части (items.#.field)
            if (preg_match('/^items\.#\.(.+)$/', $field, $matches)) {
                $fieldName = $matches[1];
                Log::debug('Найдено табличное поле:', ['field' => $field, 'fieldName' => $fieldName]);

                // Парсим ячейку для определения строки и колонки
                preg_match('/([A-Z]+)(\d+)/', $cell, $cellMatches);

                Log::debug('Результат парсинга ячейки:', [
                    'cell' => $cell,
                    'cellMatches' => $cellMatches,
                    'matches_count' => count($cellMatches)
                ]);

                // ИСПРАВЛЕНИЕ: Проверяем, что матчи найдены
                if (count($cellMatches) === 3) {
                    $column = $cellMatches[1];
                    $row = (int)$cellMatches[2];

                    $tableMapping['columns'][$column] = $fieldName;

                    // Определяем стартовую строку (минимальный номер строки)
                    if ($tableMapping['start_row'] === null || $row < $tableMapping['start_row']) {
                        $tableMapping['start_row'] = $row;
                    }

                    Log::debug('Успешно добавлено в табличный маппинг', [
                        'field' => $field,
                        'cell' => $cell,
                        'column' => $column,
                        'row' => $row,
                        'field_name' => $fieldName,
                        'current_start_row' => $tableMapping['start_row']
                    ]);
                } else {
                    // ДОБАВИТЬ ЛОГИРОВАНИЕ ОШИБКИ
                    Log::warning('Не удалось распарсить ячейку для табличного маппинга', [
                        'field' => $field,
                        'cell' => $cell,
                        'cellMatches' => $cellMatches
                    ]);
                }
            } else {
                Log::debug('Поле не является табличным (не matches items.#.)', ['field' => $field]);
            }
        }

        // ДОБАВИТЬ ПРОВЕРКУ И ЛОГИРОВАНИЕ
        Log::info('=== РЕЗУЛЬТАТ extractTableMapping ===', [
            'start_row' => $tableMapping['start_row'],
            'columns_count' => count($tableMapping['columns']),
            'columns' => $tableMapping['columns'],
            'has_columns' => !empty($tableMapping['columns']),
            'has_start_row' => $tableMapping['start_row'] !== null
        ]);

        return $tableMapping;
    }

    /**
     * Заполняет строку таблицы данными
     */
    protected function fillTableRow($worksheet, array $mapping, array $item, int $templateRow, int $index): void
    {
        $currentRow = $templateRow + $index;

        foreach ($mapping as $dataPath => $cellCoordinate) {
            // Пропускаем если это не табличный маппинг
            if (!str_contains($dataPath, 'invoice_items')) {
                continue;
            }

            // Получаем значение для текущей позиции
            $value = $this->getTableItemValue($dataPath, $item, $index);

            // Вычисляем новую ячейку для текущей строки
            $newCell = $this->getCellForRow($cellCoordinate, $currentRow);

            if ($value !== null) {
                $worksheet->setCellValue($newCell, $value);

                Log::debug('Table cell filled', [
                    'cell' => $newCell,
                    'data_path' => $dataPath,
                    'value' => $value,
                    'row' => $currentRow
                ]);
            }
        }
    }

    /**
     * Получает значение для табличной позиции
     */
    protected function getTableItemValue(string $dataPath, array $item, int $index): mixed
    {
        // Пример: invoice_items[0].name → получаем item['name']
        if (preg_match('/invoice_items\[\d+\]\.(.+)/', $dataPath, $matches)) {
            $field = $matches[1];
            return $item[$field] ?? null;
        }

        return null;
    }

    /**
     * Вычисляет ячейку для указанной строки
     */
    protected function getCellForRow(string $cellCoordinate, int $row): string
    {
        // Извлекаем букву колонки и меняем номер строки
        preg_match('/^([A-Z]+)(\d+)$/', $cellCoordinate, $matches);
        return $matches[1] . $row;
    }

    /**
     * Получает значение по пути данных (например: 'invoice.number')
     */
    protected function getValueByPath(array $data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            // Проверяем, существует ли ключ в текущем уровне
            if (!is_array($current) || !array_key_exists($key, $current)) {
                Log::debug('Key not found in data path', [
                    'path' => $path,
                    'current_key' => $key,
                    'available_keys' => is_array($current) ? array_keys($current) : 'NOT_ARRAY'
                ]);
                return null;
            }
            $current = $current[$key];
        }

        // Преобразуем массивы в строку
        if (is_array($current)) {
            Log::debug('Value is array, converting to string', [
                'path' => $path,
                'array' => $current
            ]);
            return json_encode($current, JSON_UNESCAPED_UNICODE);
        }

        return $current;
    }

    /**
     * Получает вложенное значение из массива по точечной нотации
     */
    protected function getNestedValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                Log::debug('Поле не найдено в getNestedValue', [
                    'path' => $path,
                    'current_key' => $key,
                    'available_keys' => array_keys($current)
                ]);
                return null;
            }
            $current = $current[$key];
        }

        Log::debug('Успешно извлечено значение getNestedValue', [
            'path' => $path,
            'value' => $current
        ]);

        return $current;
    }

    /**
     * Обрабатывает ячейки с комбинированными плейсхолдерами
     */
    protected function processCombinedPlaceholders($worksheet, array $mapping, array $data): void
    {
        foreach ($mapping as $field => $cell) {
            // Пропускаем табличные маппинги (они обрабатываются отдельно)
            if (strpos($field, 'items.#.') !== false) {
                continue;
            }

            $cellValue = $worksheet->getCell($cell)->getValue();

            if (is_string($cellValue) && preg_match_all('/\{\{([^}]+)\}\}/', $cellValue, $matches)) {
                $newValue = $cellValue;

                foreach ($matches[1] as $placeholder) {
                    $value = $this->getValueFromData($placeholder, $data);
                    if ($value !== '') {
                        $formattedValue = $this->formatValue($value, $placeholder);
                        $newValue = str_replace("{{{$placeholder}}}", $formattedValue, $newValue);
                    }
                }

                if ($newValue !== $cellValue) {
                    $worksheet->setCellValue($cell, $newValue);
                }
            } else {
                // Простая подстановка для одиночных значений
                $value = $this->getValueFromData($field, $data);
                if ($value !== '') {
                    $worksheet->setCellValue($cell, $value);
                }
            }
        }
    }

    /**
     * Вставляет строки табличной части и заполняет их данными
     */
    protected function insertTableRows($worksheet, array $templateRow, array $items)
    {
        $templateRowNumber = $templateRow['row_number'];
        $placeholders = $templateRow['placeholders'];

        Log::info('Inserting table rows', [
            'template_row' => $templateRowNumber,
            'items_count' => count($items),
            'placeholders_found' => $placeholders
        ]);

        // Если есть только одна строка шаблона, вставляем новые строки
        if (count($items) > 1) {
            $worksheet->insertNewRowBefore($templateRowNumber + 1, count($items) - 1);
        }

        // Заполняем каждую строку данными
        foreach ($items as $index => $item) {
            $currentRow = $templateRowNumber + $index;

            Log::debug('Filling table row', [
                'row' => $currentRow,
                'item_index' => $index,
                'item_data' => $item
            ]);

            // Заполняем каждую колонку в соответствии с плейсхолдерами
            foreach ($placeholders as $column => $placeholder) {
                $fieldName = str_replace('items.#.', '', $placeholder);
                $value = $item[$fieldName] ?? '';

                if ($value !== '') {
                    $formattedValue = $this->formatTableValue($value, $fieldName);
                    $worksheet->setCellValue($column . $currentRow, $formattedValue);

                    Log::debug('Table cell filled', [
                        'cell' => $column . $currentRow,
                        'field' => $fieldName,
                        'value' => $formattedValue,
                        'placeholder' => $placeholder
                    ]);
                }
            }
        }

        // Удаляем шаблонную строку после заполнения
        if (count($items) > 0) {
            $worksheet->removeRow($templateRowNumber + count($items));
        }
    }

    /**
     * Форматирование значений для табличной части - УЛУЧШЕННЫЙ
     */
    protected function formatTableValue($value, string $fieldName)
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Форматирование числовых значений
        if (is_numeric($value)) {
            // Денежные значения
            if (in_array($fieldName, ['amount', 'price', 'total', 'vat_amount', 'total_with_vat', 'total_without_vat'])) {
                return number_format((float)$value, 2, ',', ' ');
            }

            // Количественные значения
            if ($fieldName === 'quantity') {
                return number_format((float)$value, 2, ',', ' ');
            }

            // Проценты и коды
            if (in_array($fieldName, ['vat_rate', 'code'])) {
                return number_format((float)$value, 0);
            }
        }

        // Для текстовых значений - возвращаем как есть
        return $value;
    }

    /**
     * Обработка коллекции items для создания плейсхолдеров items.#.field
     */
    protected function processItemsCollection(array $items, string $prefix): array
    {
        $result = [];

        foreach ($items as $index => $item) {
            if (is_array($item)) {
                foreach ($item as $field => $value) {
                    $placeholderKey = $prefix . '.#.' . $field;
                    $result[$placeholderKey] = $value;

                    // Также создаем плейсхолдеры для конкретных индексов (items.0.field)
                    $indexKey = $prefix . '.' . $index . '.' . $field;
                    $result[$indexKey] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Замена плейсхолдеров во всем листе
     */
    protected function replacePlaceholdersInWorksheet($worksheet, array $data)
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        // Создаем плоский массив данных
        $flatData = $this->flattenArray($data);

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getValue();

                if (is_string($cellValue)) {
                    $newValue = $this->replacePlaceholdersInString($cellValue, $flatData);
                    if ($newValue !== $cellValue) {
                        $cell->setValue($newValue);
                    }
                }
            }
        }
    }

    /**
     * Замена плейсхолдеров в строке
     */
    protected function replacePlaceholdersInString(string $value, array $flatData): string
    {
        $newValue = $value;

        // Ищем плейсхолдеры вида {{field.path}}
        if (preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches)) {
            foreach ($matches[1] as $placeholder) {
                $replacement = $flatData[$placeholder] ?? '';
                if ($replacement !== '') {
                    $formattedReplacement = $this->formatValue($replacement, $placeholder);
                    $newValue = str_replace("{{{$placeholder}}}", $formattedReplacement, $newValue);

                    Log::debug('Замена плейсхолдера в строке', [
                        'placeholder' => $placeholder,
                        'replacement' => $formattedReplacement,
                        'original_value' => $value,
                        'new_value' => $newValue
                    ]);
                }
            }
        }

        return $newValue;
    }

    /**
     * Обрабатывает коллекции данных (например, позиции в табличной части УПД)
     */
    protected function processCollection($worksheet, $field, $cell, $data)
    {
        Log::debug('Обработка коллекции', ['field' => $field, 'cell' => $cell]);

        // Разбираем путь к полю (например, items.#.name -> ['items', '#', 'name'])
        $fieldParts = explode('.', $field);
        $collectionName = $fieldParts[0]; // items
        $collectionItemKey = $fieldParts[2]; // name

        // Получаем коллекцию из данных
        $collection = $this->getValueFromData($collectionName, $data);
        if (! is_array($collection) || empty($collection)) {
            Log::warning('Коллекция не найдена или пуста', ['collection' => $collectionName]);
            return;
        }

        // Определяем стартовую строку для вставки
        [$column, $startRow] = Coordinate::coordinateFromString($cell);
        $startRow = (int) $startRow;

        // Вставляем данные для каждого элемента коллекции
        foreach ($collection as $index => $item) {
            $currentRow = $startRow + $index;
            $value = $item[$collectionItemKey] ?? '';

            $cellCoordinate = $column.$currentRow;
            $worksheet->setCellValue($cellCoordinate, $value);
            Log::debug('Вставка значения коллекции', [
                'cell' => $cellCoordinate,
                'value' => $value,
                'index' => $index,
            ]);
        }
    }

    protected function getValueFromData(string $path, array $data)
    {
        Log::debug('Getting value from data', ['path' => $path]);

        // Обработка статических плейсхолдеров (upd.number, seller.name и т.д.)
        $pathParts = explode('.', $path);
        $current = $data;

        foreach ($pathParts as $part) {
            if (is_array($current) && array_key_exists($part, $current)) {
                $current = $current[$part];
            } else {
                Log::debug('Path part not found in data', [
                    'path' => $path,
                    'current_part' => $part,
                    'available_keys' => is_array($current) ? array_keys($current) : 'NOT_ARRAY'
                ]);
                return '';
            }
        }

        $result = is_array($current) ? '' : $current;

        Log::debug('Value retrieved from data', [
            'path' => $path,
            'result' => $result
        ]);

        return $result;
    }

    /**
     * Обработка динамических плейсхолдеров items.#.field
     */
    protected function processDynamicItems($sheet, string $col, int $row, string $fieldName, array $data)
    {
        // Находим все строки с таким же плейсхолдером в этом столбце
        $itemsData = $data['items'] ?? [];
        $currentRow = $row;

        foreach ($itemsData as $index => $item) {
            if (isset($item[$fieldName])) {
                $cell = $sheet->getCell($col . $currentRow);
                $cell->setValue($item[$fieldName]);
                $currentRow++;
            }
        }

        // Возвращаем пустое значение для исходной ячейки, так как мы уже заполнили таблицу
        return '';
    }

    /**
     * Генерация и сохранение документа в указанное место
     */
    public function generateAndSaveDocument(DocumentTemplate $template, array $data, string $savePath)
    {
        try {
            Log::info('Генерация и сохранение документа', [
                'template_id' => $template->id,
                'save_path' => $savePath
            ]);

            $templatePath = $template->file_path;
            $fullTemplatePath = Storage::disk('public')->path($templatePath);

            if (!file_exists($fullTemplatePath)) {
                throw new \Exception("Файл шаблона не найден: {$fullTemplatePath}");
            }

            // Загружаем Excel файл
            $spreadsheet = IOFactory::load($fullTemplatePath);

            // Обрабатываем все листы
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $this->processSheetWithAutoMapping($sheet, $data);
            }

            // Сохраняем напрямую в конечное место
            $fullSavePath = Storage::disk('private')->path($savePath);

            // Создаем директорию если не существует
            $directory = dirname($fullSavePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($fullSavePath);

            Log::info('Документ успешно сохранен', [
                'save_path' => $savePath,
                'full_save_path' => $fullSavePath,
                'file_exists' => file_exists($fullSavePath)
            ]);

            return $fullSavePath;

        } catch (\Exception $e) {
            Log::error('Ошибка генерации и сохранения документа', [
                'template_id' => $template->id,
                'save_path' => $savePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Генерация документа в память (без сохранения на диск)
     */
    public function generateDocumentInMemory(DocumentTemplate $template, array $data): string
    {
        $tempFiles = [];

        try {
            Log::info('=== GENERATE DOCUMENT WITH HYBRID APPROACH ===', [
                'template_id' => $template->id,
                'has_mapping' => !empty($template->mapping),
                'data_structure' => array_keys($data)
            ]);

            // Загружаем шаблон
            $templatePath = $this->getTemplatePath($template);
            $spreadsheet = IOFactory::load($templatePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // ШАГ 1: Всегда выполняем автоматическую замену плейсхолдеров для всех полей
            Log::info('Starting automatic placeholder replacement for all fields');
            $this->replaceAllPlaceholders($worksheet, $data);

            // ШАГ 2: Если есть маппинг для табличной части - обрабатываем его
            $mapping = $template->mapping ?? [];
            if (!empty($mapping)) {
                Log::info('Processing table section with mapping', [
                    'mapping_fields_count' => count($mapping)
                ]);
                $this->processTableSection($worksheet, $mapping, $data);
            } else {
                Log::info('Processing table items automatically');
                $this->processTableItems($worksheet, $data);
            }

            // ШАГ 3: Добавляем QR-код если есть
            if (!empty($data['payment_qr_code'])) {
                $qrCell = $this->findQrCodeCell($worksheet);
                if ($qrCell) {
                    $this->addQrCodeToWorksheet($worksheet, $data['payment_qr_code'], $qrCell);
                    Log::info('QR-код добавлен в документ', ['cell' => $qrCell]);
                }
            }

            // Сохраняем в память
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            Log::info('=== DOCUMENT GENERATION WITH HYBRID APPROACH SUCCESS ===', [
                'template_id' => $template->id,
                'content_size' => strlen($content)
            ]);

            return $content;

        } catch (\Exception $e) {
            Log::error('Error generating document with hybrid approach', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            // Очищаем временные файлы
            if (isset($worksheet)) {
                $this->cleanupTempFiles($worksheet);
            }
        }
    }

    /**
     * Поиск ячейки для QR-кода в шаблоне
     */
    protected function findQrCodeCell($worksheet): ?string
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        Log::debug('Поиск ячейки для QR-кода в объединенных ячейках Q2:Q8');

        // Ищем плейсхолдер в указанном диапазоне
        for ($row = 2; $row <= 8; $row++) {
            for ($col = 'Q'; $col <= 'S'; $col++) { // Q, R, S колонки
                try {
                    $cellCoordinate = $col . $row;
                    $cell = $worksheet->getCell($cellCoordinate);
                    $value = $cell->getValue();

                    if (is_string($value) && strpos($value, '{{payment_qr_code}}') !== false) {
                        Log::info('Найдена ячейка для QR-кода в объединенных ячейках', [
                            'cell' => $cellCoordinate,
                            'value' => $value
                        ]);

                        // ВАЖНО: Полностью очищаем ячейку от любых данных
                        $cell->setValue('');
                        return 'Q2'; // Всегда возвращаем Q2 как стартовую ячейку для объединенных
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        Log::warning('Не найдена ячейка с плейсхолдером в диапазоне Q2:S8');
        return 'Q2'; // Все равно используем Q2 для вставки
    }

    /**
     * Улучшенная обработка маппинга с поддержкой комбинированных полей
     */
    protected function processTemplateMapping($worksheet, DocumentTemplate $template, array $data): void
    {
        $mapping = $template->mapping ?? [];

        if (empty($mapping)) {
            Log::warning('Template mapping is empty');
            return;
        }

        Log::info('Processing template mapping', [
            'mapping_fields_count' => count($mapping),
            'mapping_fields' => array_keys($mapping)
        ]);

        $replacedCount = 0;

        foreach ($mapping as $field => $cell) {
            try {
                // Пропускаем табличные маппинги (они обрабатываются отдельно)
                if (strpos($field, 'items.#.') !== false) {
                    continue;
                }

                // Получаем значение из данных
                $value = $this->getValueFromData($field, $data);

                if ($value !== '' && $value !== null) {
                    // Форматируем значение
                    $formattedValue = $this->formatValue($value, $field);

                    // Устанавливаем значение в ячейку
                    $worksheet->setCellValue($cell, $formattedValue);
                    $replacedCount++;

                    Log::debug('Mapping placeholder replaced', [
                        'field' => $field,
                        'cell' => $cell,
                        'value' => $formattedValue
                    ]);
                } else {
                    Log::debug('No value found for mapping field', [
                        'field' => $field,
                        'cell' => $cell
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error processing mapping field', [
                    'field' => $field,
                    'cell' => $cell,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Template mapping processing completed', [
            'replaced_count' => $replacedCount,
            'total_fields' => count($mapping)
        ]);
    }

    /**
     * ПРОСТАЯ ЗАМЕНА ПЛЕЙСХОЛДЕРОВ - С ТАБЛИЧНОЙ ЧАСТЬЮ
     */
    protected function simpleReplacePlaceholders($worksheet, array $data)
    {
        Log::info('=== SIMPLE REPLACE PLACEHOLDERS START ===');

        // 1. Сначала обрабатываем табличную часть (ВЫЗОВ НОВОГО МЕТОДА)
        $this->processTableItems($worksheet, $data);

        // 2. Затем заменяем обычные плейсхолдеры (кроме items.#)
        $flatData = $this->flattenArray($data);

        Log::info('Flat data for replacement', [
            'keys_count' => count($flatData),
            'sample_keys' => array_slice(array_keys($flatData), 0, 10)
        ]);

        $replacementsCount = 0;

        // Проходим по всем ячейкам
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);

            foreach ($cellIterator as $cell) {
                $originalValue = $cell->getValue();

                if (is_string($originalValue)) {
                    $newValue = $originalValue;

                    // Заменяем ВСЕ плейсхолдеры в этой ячейке (кроме items.#)
                    foreach ($flatData as $key => $value) {
                        // Пропускаем плейсхолдеры табличной части - они уже обработаны
                        if (strpos($key, 'items.#.') !== false) {
                            continue;
                        }

                        $placeholder = '{{' . $key . '}}';
                        if (strpos($newValue, $placeholder) !== false) {
                            $formattedValue = $this->formatValue($value, $key);
                            $newValue = str_replace($placeholder, $formattedValue, $newValue);
                            $replacementsCount++;
                        }
                    }

                    // Если были замены - устанавливаем новое значение
                    if ($newValue !== $originalValue) {
                        $cell->setValue($newValue);
                    }
                }
            }
        }

        Log::info('=== SIMPLE REPLACE PLACEHOLDERS COMPLETE ===', [
            'total_replacements' => $replacementsCount
        ]);
    }

    /**
     * Форматирование специальных типов данных
     */
    protected function formatValue($value, $fieldPath)
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Форматирование денежных значений
        if (str_contains($fieldPath, 'amount') ||
            str_contains($fieldPath, 'price') ||
            str_contains($fieldPath, 'total') ||
            str_contains($fieldPath, 'cost')) {
            if (is_numeric($value)) {
                return number_format($value, 2, ',', ' ');
            }
        }

        // Форматирование количественных значений
        if (str_contains($fieldPath, 'quantity')) {
            if (is_numeric($value)) {
                return number_format($value, 2, ',', ' ');
            }
        }

        // Форматирование дат
        if (str_contains($fieldPath, 'date')) {
            try {
                if ($value instanceof \Carbon\Carbon) {
                    return $value->format('d.m.Y');
                }

                // Попытка распарсить строку как дату
                if (is_string($value) && !empty($value)) {
                    $carbonDate = \Carbon\Carbon::parse($value);
                    return $carbonDate->format('d.m.Y');
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка форматирования даты', [
                    'field' => $fieldPath,
                    'value' => $value,
                    'error' => $e->getMessage()
                ]);
                return $value; // Возвращаем оригинальное значение при ошибке
            }
        }

        // Для всех остальных случаев возвращаем строковое представление
        return (string)$value;
    }

    /**
     * Диагностика данных и шаблона
     */
    public function debugTemplateAndData(DocumentTemplate $template, array $data)
    {
        try {
            $templatePath = $template->file_path;
            $fullTemplatePath = Storage::disk('public')->path($templatePath);

            if (!file_exists($fullTemplatePath)) {
                throw new \Exception("Файл шаблона не найден: {$fullTemplatePath}");
            }

            // Загружаем Excel файл
            $spreadsheet = IOFactory::load($fullTemplatePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Ищем плейсхолдеры в первых 50 строках и 10 колонках
            $foundPlaceholders = [];
            for ($row = 1; $row <= 50; $row++) {
                for ($col = 'A'; $col <= 'J'; $col++) { // A-J колонки
                    $cell = $worksheet->getCell($col . $row);
                    $value = $cell->getValue();

                    if (is_string($value) && preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches)) {
                        foreach ($matches[1] as $placeholder) {
                            $foundPlaceholders[] = [
                                'cell' => $col . $row,
                                'placeholder' => $placeholder,
                                'value' => $value
                            ];
                        }
                    }
                }
            }

            // Создаем плоский массив данных
            $flatData = $this->flattenArray($data);

            // Проверяем соответствие плейсхолдеров и данных
            $matchedPlaceholders = [];
            $unmatchedPlaceholders = [];

            foreach ($foundPlaceholders as $placeholderInfo) {
                $placeholder = $placeholderInfo['placeholder'];
                if (array_key_exists($placeholder, $flatData)) {
                    $matchedPlaceholders[$placeholder] = [
                        'cell' => $placeholderInfo['cell'],
                        'value' => $flatData[$placeholder],
                        'template_value' => $placeholderInfo['value']
                    ];
                } else {
                    $unmatchedPlaceholders[] = [
                        'cell' => $placeholderInfo['cell'],
                        'placeholder' => $placeholder,
                        'template_value' => $placeholderInfo['value']
                    ];
                }
            }

            return [
                'template_path' => $fullTemplatePath,
                'template_exists' => file_exists($fullTemplatePath),
                'found_placeholders' => $foundPlaceholders,
                'matched_placeholders' => $matchedPlaceholders,
                'unmatched_placeholders' => $unmatchedPlaceholders,
                'available_data_keys' => array_keys($flatData),
                'data_sample' => array_slice($flatData, 0, 10), // первые 10 элементов
                'summary' => [
                    'total_placeholders' => count($foundPlaceholders),
                    'matched' => count($matchedPlaceholders),
                    'unmatched' => count($unmatchedPlaceholders)
                ]
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Упрощенная генерация УПД (альтернативный метод)
     */
    public function generateSimpleUpd(DocumentTemplate $template, array $data)
    {
        try {
            Log::info('Упрощенная генерация УПД', ['template_id' => $template->id]);

            $templatePath = Storage::disk('public')->path($template->file_path);

            if (!file_exists($templatePath)) {
                throw new \Exception("Файл шаблона не найден: {$templatePath}");
            }

            // Загружаем шаблон
            $spreadsheet = IOFactory::load($templatePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Плоский массив данных
            $flatData = $this->flattenArray($data);

            // Проходим по всем ячейкам и заменяем плейсхолдеры
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(true);

                foreach ($cellIterator as $cell) {
                    $cellValue = $cell->getValue();

                    if (is_string($cellValue)) {
                        $newValue = $cellValue;

                        // Замена всех плейсхолдеров в ячейке
                        foreach ($flatData as $key => $value) {
                            $placeholder = '{{' . $key . '}}';
                            if (strpos($newValue, $placeholder) !== false) {
                                $formattedValue = $this->formatValue($value, $key);
                                $newValue = str_replace($placeholder, $formattedValue, $newValue);
                            }
                        }

                        if ($newValue !== $cellValue) {
                            $cell->setValue($newValue);
                        }
                    }
                }
            }

            // Сохраняем в память
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            ob_start();
            $writer->save('php://output');
            $fileContent = ob_get_clean();

            Log::info('Упрощенная генерация УПД завершена', [
                'template_id' => $template->id,
                'content_size' => strlen($fileContent)
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            Log::error('Ошибка упрощенной генерации УПД', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получить путь к файлу шаблона
     */
    protected function getTemplatePath(DocumentTemplate $template): string
    {
        $path = $template->file_path;

        // Проверяем различные возможные места хранения
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        if (Storage::exists($path)) {
            return Storage::path($path);
        }

        throw new \Exception("Template file not found: {$path}");
    }

    /**
     * Вспомогательный метод для альтернативного заполнения таблицы
     */
    protected function tryAlternativeTableFilling($worksheet, array $mapping, array $items)
    {
        Log::warning('Using alternative table filling method');
        // Реализация альтернативного метода заполнения таблицы
    }

    /**
     * Заполняет строки таблицы данными
     */
    protected function fillTableRows($worksheet, array $tableMapping, array $items)
    {
        $startRow = $tableMapping['start_row'];
        $columns = $tableMapping['columns'];

        foreach ($items as $index => $item) {
            $currentRow = $startRow + $index;

            foreach ($columns as $column => $fieldName) {
                $value = $item[$fieldName] ?? '';
                if ($value !== '') {
                    $formattedValue = $this->formatTableValue($value, $fieldName);
                    $worksheet->setCellValue($column . $currentRow, $formattedValue);
                }
            }
        }
    }

    /**
     * Добавление QR-кода в Excel документ
     */
    protected function addQrCodeToWorksheet($worksheet, $qrCodeData, $cellCoordinate): void
    {
        try {
            if (empty($qrCodeData)) {
                Log::warning('QR-код пустой, пропускаем вставку');
                return;
            }

            Log::debug('Добавление QR-кода как изображения', [
                'cell' => $cellCoordinate,
                'qr_data_size' => strlen($qrCodeData)
            ]);

            // ВАЖНО: Принудительно очищаем ячейку от любых данных
            $worksheet->setCellValue($cellCoordinate, '');

            // СОЗДАЕМ ВРЕМЕННЫЙ ФАЙЛ ТОЛЬКО ДЛЯ QR-КОДА
            $tempDir = storage_path('app/temp/');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempQrPath = $tempDir . 'qr_' . uniqid() . '.png';
            file_put_contents($tempQrPath, $qrCodeData);

            // Проверяем, что файл создан
            if (!file_exists($tempQrPath) || filesize($tempQrPath) < 100) {
                Log::error('Не удалось создать временный файл QR-кода');
                return;
            }

            // Создаем объект рисунка для Excel
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('QR Code');
            $drawing->setDescription('QR Code for payment');
            $drawing->setPath($tempQrPath);

            // Устанавливаем ячейку для вставки
            $drawing->setCoordinates($cellCoordinate);

            // Размеры изображения (в пикселях)
            $drawing->setWidth(120);
            $drawing->setHeight(120);

            // Смещение от угла ячейки
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);

            // Вставляем в документ
            $drawing->setWorksheet($worksheet);

            Log::info('QR-код успешно добавлен как изображение', [
                'cell' => $cellCoordinate,
                'image_size' => "120x120px",
                'temp_file' => $tempQrPath
            ]);

            // НЕ УДАЛЯЕМ ФАЙЛ СРАЗУ - он будет удален после завершения запроса
            // или можно добавить его в список для очистки

        } catch (\Exception $e) {
            Log::error('Ошибка добавления QR-кода как изображения', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cell' => $cellCoordinate
            ]);
        }
    }

    /**
     * Очистка временных файлов после генерации документа
     */
    protected function cleanupTempFiles($worksheet): void
    {
        try {
            $tempDir = storage_path('app/temp/');
            if (!file_exists($tempDir)) {
                return;
            }

            // Находим все рисунки в документе
            $drawings = $worksheet->getDrawingCollection();

            foreach ($drawings as $drawing) {
                $path = $drawing->getPath();
                if (file_exists($path) && strpos($path, $tempDir) !== false) {
                    unlink($path);
                    Log::debug('Удален временный файл изображения', ['path' => $path]);
                }
            }

            // Дополнительная очистка старых файлов (больше 1 часа)
            $files = glob($tempDir . 'qr_*.png');
            foreach ($files as $file) {
                if (filemtime($file) < time() - 3600) {
                    unlink($file);
                }
            }

        } catch (\Exception $e) {
            Log::error('Ошибка очистки временных файлов', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
