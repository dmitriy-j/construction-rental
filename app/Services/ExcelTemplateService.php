<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelTemplateService
{
    public function parseUpdFile(string $filePath, array $mapping, array $updSettings): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $data = [];

        foreach ($mapping as $field => $config) {
            $value = $this->getCellValue($worksheet, $config['cell']);

            if (isset($config['transform'])) {
                $value = $this->applyTransform($value, $config['transform'], $updSettings);
            }

            $data[$field] = $value;
        }

        // Валидация обязательных полей
        $this->validateUpdData($data, $updSettings);

        return $data;
    }

    protected function applyTransform($value, string $transform, array $updSettings)
    {
        switch ($transform) {
            case 'date':
                return $this->transformDate($value, $updSettings['date_format'] ?? 'd.m.Y');
            case 'numeric':
                return (float) preg_replace('/[^0-9.]/', '', $value);
            case 'vat_rate':
                return $this->transformVatRate($value, $updSettings);
            case 'string':
            default:
                return (string) $value;
        }
    }

    protected function transformDate($value, string $format): ?string
    {
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Не удалось преобразовать дату', ['value' => $value, 'format' => $format]);

            return null;
        }
    }

    protected function transformVatRate($value, array $updSettings): float
    {
        $value = strtolower(trim((string) $value));
        $vatRates = $updSettings['vat_rates'] ?? [];

        foreach ($vatRates as $pattern => $rate) {
            if (strpos($value, strtolower($pattern)) !== false) {
                return (float) $rate;
            }
        }

        return (float) ($updSettings['default_vat_rate'] ?? 20);
    }

    protected function validateUpdData(array $data, array $updSettings): void
    {
        $requiredFields = $updSettings['required_fields'] ?? ['number', 'issue_date', 'amount', 'total_amount'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Обязательное поле {$field} отсутствует или пустое.");
            }
        }

        // Проверка формата номера УПД
        if (isset($data['number'])) {
            $this->validateUpdNumber($data['number'], $updSettings);
        }

        // Проверка согласованности сумм
        if (isset($data['amount']) && isset($data['tax_amount']) && isset($data['total_amount'])) {
            $calculatedTotal = (float) $data['amount'] + (float) $data['tax_amount'];
            $declaredTotal = (float) $data['total_amount'];

            if (abs($calculatedTotal - $declaredTotal) > 0.01) {
                throw new \InvalidArgumentException(
                    "Суммы не сходятся: без НДС {$data['amount']} + НДС {$data['tax_amount']} ≠ {$data['total_amount']}"
                );
            }
        }
    }

    protected function validateUpdNumber(string $number, array $updSettings): void
    {
        $patterns = $updSettings['number_patterns'] ?? ['УПД', 'Счет-фактура', 'СФ'];
        $isValid = false;

        foreach ($patterns as $pattern) {
            if (stripos($number, $pattern) !== false) {
                $isValid = true;
                break;
            }
        }

        if (! $isValid) {
            throw new \InvalidArgumentException(
                'Номер документа не соответствует ожидаемому формату. Ожидается один из: '.implode(', ', $patterns)
            );
        }
    }
}
