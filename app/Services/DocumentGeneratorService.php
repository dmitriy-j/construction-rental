<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;

class DocumentGeneratorService
{
    public function generateDocument(DocumentTemplate $template, array $data)
    {
        try {
            Log::info('Начало генерации документа', ['template_id' => $template->id]);

            // Загружаем шаблон
            $templatePath = Storage::disk('public')->path($template->file_path);
            Log::debug('Путь к файлу шаблона', ['path' => $templatePath]);

            if (!file_exists($templatePath)) {
                throw new \Exception("Файл шаблона не существует: " . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $worksheet = $spreadsheet->getActiveSheet();
            Log::info('Шаблон успешно загружен');

            // Заменяем данные в ячейках согласно маппингу
            $mapping = $template->mapping;
            Log::debug('Настройки маппинга', ['mapping' => $mapping]);

            foreach ($mapping as $field => $cell) {
                $value = $this->getValueFromData($field, $data);
                Log::debug('Подстановка значения в ячейку', [
                    'field' => $field,
                    'cell' => $cell,
                    'value' => $value
                ]);

                $worksheet->setCellValue($cell, $value);
            }

            // Сохраняем временный файл
            $fileName = 'document_' . time() . '.xlsx';
            $tempPath = storage_path('app/temp/' . $fileName);

            // Создаем директорию, если она не существует
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
                Log::info('Создана директория для временных файлов', ['path' => dirname($tempPath)]);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempPath);

            Log::info('Документ успешно сохранен', ['path' => $tempPath]);

            return $tempPath;

        } catch (\Exception $e) {
            Log::error('Ошибка в сервисе генерации документов', [
                'template_id' => $template->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    protected function getValueFromData(string $field, array $data)
    {
        try {
            Log::debug('Извлечение значения из данных', ['field' => $field]);

            $keys = explode('.', $field);
            $value = $data;

            foreach ($keys as $key) {
                if (!isset($value[$key])) {
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
                'error_message' => $e->getMessage()
            ]);

            return '';
        }
    }
}
