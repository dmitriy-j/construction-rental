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
    protected function replacePlaceholders($worksheet, $data)
    {
        Log::info('Начало замены плейсхолдеров в ячейках');

        // Получаем максимальные строку и колонку
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Проходим по всем ячейкам
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($col).$row;
                $cell = $worksheet->getCell($cellCoordinate);
                $cellValue = $cell->getValue();

                // Если в ячейке есть плейсхолдеры {{ }}
                if (is_string($cellValue) && preg_match_all('/\{\{\s*([\w\.]+)\s*\}\}/', $cellValue, $matches)) {
                    $newValue = $cellValue;

                    // Заменяем каждый найденный плейсхолдер
                    foreach ($matches[1] as $placeholder) {
                        $fieldPath = trim($placeholder);
                        $value = $this->getValueFromData($fieldPath, $data);

                        // Форматируем специальные типы данных
                        $value = $this->formatValue($value, $fieldPath);

                        // Заменяем плейсхолдер на значение
                        $newValue = str_replace("{{{$placeholder}}}", $value, $newValue);
                    }

                    // Устанавливаем новое значение ячейки
                    if ($newValue !== $cellValue) {
                        $worksheet->setCellValue($cellCoordinate, $newValue);
                        Log::debug('Замена плейсхолдера в ячейке', [
                            'cell' => $cellCoordinate,
                            'old_value' => $cellValue,
                            'new_value' => $newValue,
                        ]);
                    }
                }
            }
        }
    }

    protected function getValueFromData(string $field, array $data)
    {
        try {
            Log::debug('Извлечение значения из данных', ['field' => $field]);

            // Прямой доступ к полю (например, 'upd_number')
            if (isset($data[$field])) {
                return $data[$field];
            }

            // Доступ через точную нотацию (например, 'upd.number')
            $keys = explode('.', $field);
            $value = $data;

            foreach ($keys as $key) {
                if (! isset($value[$key])) {
                    Log::warning('Ключ не найден в данных', ['key' => $key, 'available_keys' => array_keys($value)]);

                    return '';
                }
                $value = $value[$key];
            }

            Log::debug('Значение успешно извлечено', ['field' => $field, 'value' => $value]);

            return $value;

        } catch (\Exception $e) {
            Log::error('Ошибка извлечения значения', [
                'field' => $field,
                'error_message' => $e->getMessage(),
            ]);

            return '';
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
