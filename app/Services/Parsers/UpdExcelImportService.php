<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UpdExcelParser
{
    public function parse(string $filePath, array $mapping): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $data = [];

            foreach ($mapping as $field => $config) {
                $value = $this->getCellValue($worksheet, $config['cell']);

                if (isset($config['transform'])) {
                    $value = $this->applyTransform($value, $config['transform']);
                }

                $data[$field] = $value;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Ошибка парсинга Excel файла УПД', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Не удалось распарсить файл УПД: '.$e->getMessage());
        }
    }

    protected function getCellValue(Worksheet $worksheet, string $cell): ?string
    {
        return $worksheet->getCell($cell)->getValue();
    }

    protected function applyTransform($value, string $transform)
    {
        switch ($transform) {
            case 'date':
                return $this->transformDate($value);
            case 'numeric':
                return (float) preg_replace('/[^0-9.]/', '', $value);
            case 'string':
            default:
                return (string) $value;
        }
    }

    protected function transformDate($value): ?string
    {
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Не удалось преобразовать дату', ['value' => $value]);

            return null;
        }
    }

    public function validateData(array $data): array
    {
        $validator = Validator::make($data, [
            'number' => 'required|string',
            'issue_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Данные УПД невалидны: '.$validator->errors()->first());
        }

        return $validator->validated();
    }
}
