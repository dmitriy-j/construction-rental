<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UpdParserService
{
    /**
     * Парсинг УПД из Excel файла по конфигурации маппинга
     */
    public function parseUpdFromExcel(string $filePath, array $mappingConfig): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $parsedData = [
                'header' => [],
                'amounts' => [],
                'items' => [],
            ];

            // Парсим заголовок
            foreach ($mappingConfig['header'] as $field => $config) {
                if (isset($config['cell'])) {
                    $value = $this->getCellValue($worksheet, $config['cell']);

                    // Применяем парсер, если он указан
                    if (isset($config['parser'])) {
                        $value = $this->applyParser($value, $config['parser'], $field);
                    }

                    $parsedData['header'][$field] = $value;
                } else {
                    // Обработка вложенных полей
                    foreach ($config as $subField => $subConfig) {
                        $value = $this->getCellValue($worksheet, $subConfig['cell']);

                        // Применяем парсер, если он указан
                        if (isset($subConfig['parser'])) {
                            $value = $this->applyParser($value, $subConfig['parser'], $subField);
                        }

                        $parsedData['header'][$field][$subField] = $value;
                    }
                }
            }

            // Парсим суммы
            foreach ($mappingConfig['amounts'] as $field => $config) {
                $parsedData['amounts'][$field] = $this->getCellValue($worksheet, $config['cell']);
            }

            // Парсим табличную часть
            if (isset($mappingConfig['items'])) {
                $startRow = $mappingConfig['items']['start_row'];
                $row = $startRow;

                while ($this->hasItemData($worksheet, $row, $mappingConfig['items']['columns'])) {
                    $item = [];

                    foreach ($mappingConfig['items']['columns'] as $field => $columnConfig) {
                        $cell = $columnConfig['cell'].$row;
                        $item[$field] = $this->getCellValue($worksheet, $cell);
                    }

                    if ($this->isValidItem($item)) {
                        $parsedData['items'][] = $item;
                    }

                    $row++;
                }
            }

            return $parsedData;

        } catch (\Exception $e) {
            Log::error('Ошибка парсинга УПД', ['error' => $e->getMessage()]);
            throw new \Exception('Не удалось распарсить файл УПД: '.$e->getMessage());
        }
    }

    protected function applyParser($value, string $parser, string $fieldName)
    {
        switch ($parser) {
            case 'inn_kpp':
                return $this->parseInnKpp($value, $fieldName);
            case 'split_slash':
                return $this->splitBySlash($value, $fieldName);
            case 'extract_numbers':
                return preg_replace('/[^0-9]/', '', $value);
            default:
                return $value;
        }
    }

    protected function parseInnKpp($value, string $fieldName)
    {
        // Извлекаем ИНН и КПП из строки вида "9718028359/569973429"
        if (preg_match('/(\d+)\/(\d+)/', $value, $matches)) {
            // В зависимости от имени поля возвращаем соответствующую часть
            if (strpos($fieldName, 'inn') !== false) {
                return $matches[1];
            } elseif (strpos($fieldName, 'kpp') !== false) {
                return $matches[2];
            }
        }

        // Если не удалось распарсить, возвращаем исходное значение
        return $value;
    }

    protected function splitBySlash($value, string $fieldName)
    {
        $parts = explode('/', $value);

        // В зависимости от имени поля возвращаем соответствующую часть
        if (strpos($fieldName, 'inn') !== false && count($parts) > 0) {
            return trim($parts[0]);
        } elseif (strpos($fieldName, 'kpp') !== false && count($parts) > 1) {
            return trim($parts[1]);
        }

        return $value;
    }

    protected function getCellValue(Worksheet $worksheet, string $cell): ?string
    {
        try {
            $value = $worksheet->getCell($cell)->getValue();

            if ($value === null) {
                $value = $this->getValueFromMergedCell($worksheet, $cell);
            }

            return $value !== null ? (string) $value : null;

        } catch (\Exception $e) {
            Log::error('Ошибка получения значения ячейки', [
                'cell' => $cell,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function getValueFromMergedCell(Worksheet $worksheet, string $cell): ?string
    {
        try {
            $mergedCells = $worksheet->getMergeCells();

            foreach ($mergedCells as $mergedRange) {
                [$startCell, $endCell] = explode(':', $mergedRange);

                if ($this->isCellInRange($cell, $startCell, $endCell)) {
                    return $worksheet->getCell($startCell)->getValue();
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка получения значения из объединенной ячейки', [
                'cell' => $cell,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function isCellInRange(string $cell, string $startCell, string $endCell): bool
    {
        try {
            // Разбиваем координаты на составные части
            [$cellColumn, $cellRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($cell);
            $cellColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cellColumn);
            $cellRow = (int) $cellRow;

            [$startCellColumn, $startCellRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($startCell);
            $startColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCellColumn);
            $startRowIndex = (int) $startCellRow;

            [$endCellColumn, $endCellRow] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($endCell);
            $endColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($endCellColumn);
            $endRowIndex = (int) $endCellRow;

            // Проверяем, входит ли ячейка в диапазон
            return $cellColumnIndex >= $startColumnIndex &&
                    $cellColumnIndex <= $endColumnIndex &&
                    $cellRow >= $startRowIndex &&
                    $cellRow <= $endRowIndex;

        } catch (\Exception $e) {
            Log::error('Ошибка проверки диапазона ячейки', [
                'cell' => $cell,
                'startCell' => $startCell,
                'endCell' => $endCell,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function hasItemData(Worksheet $worksheet, int $row, array $columns): bool
    {
        // Проверяем, есть ли данные в первой колонке товара
        $firstColumn = reset($columns)['cell'].$row;

        return $this->getCellValue($worksheet, $firstColumn) !== null;
    }

    protected function isValidItem(array $item): bool
    {
        // Проверяем, что товар имеет название и количество
        return ! empty($item['name']) && ! empty($item['quantity']);
    }

    /**
     * Валидация распарсенных данных
     */
    public function validateParsedData(array $parsedData): void
    {
        $requiredFields = [
            'header.number',
            'header.issue_date',
            'header.seller.name',
            'header.seller.inn',
            'header.buyer.name',
            'header.buyer.inn',
            'amounts.total',
        ];

        foreach ($requiredFields as $field) {
            $value = data_get($parsedData, $field);
            if (empty($value)) {
                throw new \InvalidArgumentException("Обязательное поле {$field} отсутствует или пустое");
            }
        }

        // Проверка согласованности сумм
        if (! empty($parsedData['amounts']['without_vat']) &&
            ! empty($parsedData['amounts']['vat']) &&
            ! empty($parsedData['amounts']['total'])) {

            $calculatedTotal = (float) $parsedData['amounts']['without_vat'] + (float) $parsedData['amounts']['vat'];
            $declaredTotal = (float) $parsedData['amounts']['total'];

            if (abs($calculatedTotal - $declaredTotal) > 0.01) {
                throw new \InvalidArgumentException(
                    "Суммы не сходятся: без НДС {$parsedData['amounts']['without_vat']} + ".
                    "НДС {$parsedData['amounts']['vat']} ≠ {$parsedData['amounts']['total']}"
                );
            }
        }
    }
}
