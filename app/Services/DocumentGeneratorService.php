<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
            $this->replacePlaceholders($worksheet, $data);

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
            Log::info('Генерация документа с автоматическим маппингом', [
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

            // Сохраняем временный файл
            $tempFileName = 'temp/document_' . time() . '.xlsx';
            $tempPath = Storage::disk('private')->path($tempFileName);

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempPath);

            Log::info('Документ сгенерирован с автоматическим маппингом', [
                'temp_path' => $tempFileName
            ]);

            return $tempPath;

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
            'columns' => $highestColumn
        ]);

        // Сканируем все ячейки на листе
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cell = $sheet->getCell($col . $row);
                $value = $cell->getValue();

                if (is_string($value)) {
                    $newValue = $this->replacePlaceholders($value, $data, $sheet, $col, $row);
                    if ($newValue !== $value) {
                        $cell->setValue($newValue);
                    }
                }
            }
        }
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

    /**
     * Ищет и заменяет плейсхолдеры вида {{field.path}} во всех ячейках листа
     */
    protected function replacePlaceholders(string $value, array $data, $sheet, string $col, int $row): string
    {
        // Ищем плейсхолдеры вида {{field.path}}
        if (preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches)) {
            foreach ($matches[1] as $placeholder) {
                $replacement = $this->getValueFromData($placeholder, $data, $sheet, $col, $row);
                $value = str_replace("{{{$placeholder}}}", $replacement, $value);
            }
        }

        return $value;
    }

    protected function getValueFromData(string $path, array $data, $sheet, string $col, int $row)
    {
        // Обработка динамических плейсхолдеров для табличной части
        if (preg_match('/^items\.#\.(.+)$/', $path, $matches)) {
            $fieldName = $matches[1];
            return $this->processDynamicItems($sheet, $col, $row, $fieldName, $data);
        }

        // Обработка статических плейсхолдеров (items.0.name, items.1.quantity и т.д.)
        if (preg_match('/^items\.(\d+)\.(.+)$/', $path, $matches)) {
            $itemIndex = $matches[1];
            $fieldName = $matches[2];

            if (isset($data['items'][$itemIndex][$fieldName])) {
                return $data['items'][$itemIndex][$fieldName];
            }
            return '';
        }

        // Обработка обычных полей
        $pathParts = explode('.', $path);
        $current = $data;

        foreach ($pathParts as $part) {
            if (is_array($current) && array_key_exists($part, $current)) {
                $current = $current[$part];
            } else {
                return '';
            }
        }

        return is_array($current) ? '' : $current;
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
    public function generateDocumentInMemory(DocumentTemplate $template, array $data)
    {
        try {
            Log::info('Генерация документа в память', [
                'template_id' => $template->id,
                'data_keys' => array_keys($data)
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

            // Сохраняем в память
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

            ob_start();
            $writer->save('php://output');
            $fileContent = ob_get_clean();

            Log::info('Документ сгенерирован в память', [
                'template_id' => $template->id,
                'content_size' => strlen($fileContent)
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            Log::error('Ошибка генерации документа в память', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Форматирование специальных типов данных
     */
    protected function formatValue($value, $fieldPath)
    {
        // Форматирование денежных значений
        if (str_contains($fieldPath, 'amount') || str_contains($fieldPath, 'price') || str_contains($fieldPath, 'total')) {
            return is_numeric($value) ? number_format($value, 2, ',', ' ') : $value;
        }

        // Форматирование дат
        if (str_contains($fieldPath, 'date')) {
            try {
                if (is_string($value) && ! empty($value)) {
                    return $value;
                }

                if ($value instanceof \Carbon\Carbon) {
                    return $value->format('d.m.Y');
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка форматирования даты', ['field' => $fieldPath, 'value' => $value]);
            }
        }

        return $value;
    }
}
