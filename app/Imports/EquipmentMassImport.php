<?php
// app/Imports/EquipmentMassImport.php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class EquipmentMassImport implements ToCollection, WithHeadingRow
{
    private $data = [];
    private $errors = [];

    public function collection(Collection $rows)
    {
        Log::info("Начало обработки Excel файла", ['total_rows' => $rows->count()]);

        // Логируем заголовки для отладки
        if ($rows->count() > 0) {
            $firstRow = $rows->first();
            Log::info("Заголовки из первой строки", ['headers' => array_keys($firstRow->toArray())]);
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 т.к. +1 для заголовка и +1 для 1-based индекса

            // Логируем сырые данные строки для отладки
            Log::debug("Обработка строки $rowNumber", ['raw_data' => $row->toArray()]);

            // Пропускаем пояснительные строки
            if ($this->isInstructionRow($row)) {
                Log::debug("Пропущена пояснительная строка", ['row' => $rowNumber]);
                continue;
            }

            // Пропускаем полностью пустые строки
            if ($this->isEmptyRow($row)) {
                Log::debug("Пропущена пустая строка", ['row' => $rowNumber]);
                continue;
            }

            try {
                $normalizedRow = $this->normalizeRow($row);
                Log::debug("Нормализованная строка", ['row' => $rowNumber, 'normalized' => $normalizedRow]);

                // Проверяем обязательные поля
                if (!$this->hasRequiredFields($normalizedRow)) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'error' => 'Отсутствуют обязательные поля',
                        'data' => $normalizedRow
                    ];
                    Log::warning("Пропущена строка с отсутствующими полями", [
                        'row' => $rowNumber,
                        'data' => $normalizedRow
                    ]);
                    continue;
                }

                $this->data[] = $normalizedRow;
                Log::debug("Строка успешно обработана", [
                    'row' => $rowNumber,
                    'data' => $normalizedRow
                ]);

            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'error' => 'Ошибка обработки строки: ' . $e->getMessage(),
                    'data' => $row->toArray()
                ];
                Log::error("Ошибка обработки строки", [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'data' => $row->toArray()
                ]);
            }
        }

        Log::info("Импорт данных завершен", [
            'successful_rows' => count($this->data),
            'errors_count' => count($this->errors)
        ]);
    }

    private function isInstructionRow($row): bool
    {
        // Проверяем оба варианта заголовков (русские и транслитерированные)
        $title = $row['название_техники'] ??
                 $row['название техники'] ??
                 $row['nazvanie_texniki'] ?? null;

        if (empty($title)) {
            return false;
        }

        $title = trim($title);

        $instructions = [
            'Пояснения по заполнению:',
            '1. Все поля обязательны для заполнения',
            '2. ID категории можно посмотреть в списке категорий в личном кабинете',
            '3. Числовые значения вводить без единиц измерения (только цифры)',
            '4. Удалите примеры перед заполнением своими данными',
        ];

        return in_array($title, $instructions) ||
               str_starts_with($title, 'Пояснения') ||
               str_starts_with($title, '1.') ||
               str_starts_with($title, '2.') ||
               str_starts_with($title, '3.') ||
               str_starts_with($title, '4.');
    }

    private function isEmptyRow($row): bool
    {
        $values = array_filter($row->toArray(), function($value) {
            return !is_null($value) && $value !== '' && $value !== ' ' && $value !== '  ';
        });

        return empty($values);
    }

    private function hasRequiredFields(array $row): bool
    {
        $required = [
            'title', 'description', 'category_id', 'brand', 'model',
            'year', 'hours_worked', 'price_per_hour', 'location_name',
            'location_address', 'weight', 'length', 'width', 'height'
        ];

        foreach ($required as $field) {
            if (empty($row[$field]) && $row[$field] !== 0 && $row[$field] !== '0') {
                Log::debug("Отсутствует обязательное поле", [
                    'field' => $field,
                    'value' => $row[$field]
                ]);
                return false;
            }
        }

        return true;
    }

    private function normalizeRow($row): array
    {
        // Нормализуем ключи, принимая оба варианта (русские и транслитерированные)
        $normalized = [
            'title' => $this->getValue($row, [
                'название_техники', 'название техники', 'nazvanie_texniki'
            ]),
            'description' => $this->getValue($row, ['описание', 'opisanie']),
            'category_id' => $this->getValue($row, [
                'id_категории', 'id категории', 'id_kategorii'
            ]),
            'brand' => $this->getValue($row, ['бренд', 'brend']),
            'model' => $this->toString($this->getValue($row, ['модель', 'model'])), // ИСПРАВЛЕНО: преобразуем в строку
            'year' => $this->getValue($row, [
                'год_выпуска', 'год выпуска', 'god_vypuska'
            ]),
            'hours_worked' => $this->getValue($row, [
                'наработка_часы', 'наработка часы', 'narabotka_casy'
            ]),
            'price_per_hour' => $this->getValue($row, [
                'цена_за_час_руб', 'цена за час руб', 'cena_za_cas_rub'
            ]),
            'location_name' => $this->getValue($row, [
                'название_локации', 'название локации', 'nazvanie_lokacii'
            ]),
            'location_address' => $this->getValue($row, [
                'адрес_локации', 'адрес локации', 'adres_lokacii'
            ]),
            'weight' => $this->getValue($row, ['вес_кг', 'вес кг', 'ves_kg']),
            'length' => $this->getValue($row, ['длина_м', 'длина м', 'dlina_m']),
            'width' => $this->getValue($row, ['ширина_м', 'ширина м', 'sirina_m']),
            'height' => $this->getValue($row, ['высота_м', 'высота м', 'vysota_m']),
        ];

        // Преобразуем числовые значения
        $normalized['category_id'] = $this->toInt($normalized['category_id']);
        $normalized['year'] = $this->toInt($normalized['year']);
        $normalized['hours_worked'] = $this->toFloat($normalized['hours_worked']);
        $normalized['price_per_hour'] = $this->toFloat($normalized['price_per_hour']);
        $normalized['weight'] = $this->toFloat($normalized['weight']);
        $normalized['length'] = $this->toFloat($normalized['length']);
        $normalized['width'] = $this->toFloat($normalized['width']);
        $normalized['height'] = $this->toFloat($normalized['height']);

        return $normalized;
    }

    private function getValue($row, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            // Проверяем наличие ключа и что значение не пустое
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                $value = $row[$key];
                Log::debug("Найдено значение для ключа", [
                    'key' => $key,
                    'value' => $value,
                    'type' => gettype($value)
                ]);
                return $value;
            }
        }

        Log::debug("Не найдено значение для ключей", ['keys' => $possibleKeys]);
        return null;
    }

    private function toInt($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private function toFloat($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        // Заменяем запятые на точки для корректного преобразования
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    private function toString($value) // ДОБАВЛЕН: метод для преобразования в строку
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return (string) $value;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
