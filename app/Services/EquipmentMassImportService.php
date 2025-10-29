<?php
// app/Services/EquipmentMassImportService.php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentImport;
use App\Models\Location;
use App\Models\Category;
use App\Models\Specification;
use App\Models\EquipmentRentalTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use App\Imports\EquipmentMassImport;
use Maatwebsite\Excel\Facades\Excel;

class EquipmentMassImportService
{
    public function processImport(string $filePath, int $companyId, int $importId): EquipmentImport
    {
        Log::info("Начало обработки импорта", [
            'import_id' => $importId,
            'company_id' => $companyId,
            'file_path' => $filePath
        ]);

        $importRecord = EquipmentImport::findOrFail($importId);
        $importRecord->markAsProcessing();

        try {
            DB::beginTransaction();
            Log::info("Транзакция начата для импорта", ['import_id' => $importId]);

            $data = $this->parseExcel($filePath);
            Log::info("Excel файл распарсен", [
                'import_id' => $importId,
                'rows_count' => count($data),
                'first_row' => $data[0] ?? 'нет данных'
            ]);

            if (empty($data)) {
                throw new \Exception("Excel файл не содержит данных для импорта или все строки были проигнорированы");
            }

            $importRecord->update(['total_rows' => count($data)]);

            // Обрабатываем все данные сразу (не чанками, т.к. мало записей)
            $results = $this->processData($data, $companyId);
            Log::info("Обработка данных завершена", [
                'import_id' => $importId,
                'results' => $results
            ]);

            $importRecord->markAsCompleted($results);
            Log::info("Импорт успешно завершен", ['import_id' => $importId]);

            DB::commit();
            Log::info("Транзакция завершена успешно", ['import_id' => $importId]);

            return $importRecord;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Ошибка при импорте", [
                'import_id' => $importId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $importRecord->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function parseExcel(string $filePath): array
    {
        Log::info("Начало парсинга Excel файла", ['file_path' => $filePath]);

        $import = new EquipmentMassImport;

        try {
            Excel::import($import, $filePath);
            Log::info("Excel файл успешно импортирован", ['file_path' => $filePath]);

            $data = $import->getData();
            $errors = $import->getErrors();

            Log::info("Данные из Excel получены", [
                'rows_count' => count($data),
                'errors_count' => count($errors),
                'first_row_sample' => $data[0] ?? 'нет данных'
            ]);

            if (!empty($errors)) {
                $errorMessage = "Обнаружены ошибки в Excel файле: ";
                foreach ($errors as $error) {
                    $errorMessage .= "Строка {$error['row']}: {$error['error']}; ";
                }
                Log::warning("Ошибки в данных Excel", ['errors' => $errors]);
                throw new \Exception($errorMessage);
            }

            if (empty($data)) {
                Log::warning("Excel файл не содержит данных для импорта");
                throw new \Exception("Excel файл не содержит данных для импорта или все строки были проигнорированы");
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("Ошибка при чтении Excel файла", [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Ошибка при чтении Excel файла: " . $e->getMessage());
        }
    }

    private function processData(array $data, int $companyId): array
    {
        $results = ['processed' => 0, 'successful' => 0, 'failed' => 0, 'errors' => []];
        $equipmentBatch = [];
        $locationsMap = [];

        Log::info("Начало обработки данных", [
            'total_rows' => count($data),
            'company_id' => $companyId
        ]);

        foreach ($data as $index => $row) {
            $absoluteRow = $index + 1;

            try {
                Log::debug("Обработка строки", [
                    'absolute_row' => $absoluteRow,
                    'row_data' => $row
                ]);

                $validated = $this->validateRow($row, $companyId);
                Log::debug("Валидация пройдена", [
                    'absolute_row' => $absoluteRow,
                    'validated_data' => $validated
                ]);

                $slug = $this->generateUniqueSlug($validated['title']);
                Log::debug("Сгенерирован slug", [
                    'absolute_row' => $absoluteRow,
                    'slug' => $slug
                ]);

                $equipmentBatch[] = [
                    'title' => $validated['title'],
                    'slug' => $slug,
                    'description' => $validated['description'],
                    'company_id' => $companyId,
                    'category_id' => $validated['category_id'],
                    'location_id' => null,
                    'brand' => $validated['brand'],
                    'model' => $validated['model'],
                    'year' => (int) $validated['year'],
                    'hours_worked' => (float) $validated['hours_worked'],
                    'is_approved' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $locationsMap[$slug] = [
                    'name' => $validated['location_name'],
                    'address' => $validated['location_address'],
                    'weight' => $validated['weight'],
                    'length' => $validated['length'],
                    'width' => $validated['width'],
                    'height' => $validated['height'],
                    'price_per_hour' => $validated['price_per_hour'],
                ];

                $results['successful']++;
                Log::debug("Строка успешно обработана", ['absolute_row' => $absoluteRow]);

            } catch (ValidationException $e) {
                // Останавливаем при первой ошибке
                $errorMessage = "Ошибка валидации в строке {$absoluteRow}: " . implode(', ', Arr::flatten($e->errors()));
                Log::error("Ошибка валидации", [
                    'absolute_row' => $absoluteRow,
                    'errors' => $e->errors(),
                    'data' => $row
                ]);
                throw new \Exception($errorMessage);
            } catch (\Exception $e) {
                // Останавливаем при любой другой ошибке
                $errorMessage = "Ошибка в строке {$absoluteRow}: " . $e->getMessage();
                Log::error("Общая ошибка обработки строки", [
                    'absolute_row' => $absoluteRow,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
                throw new \Exception($errorMessage);
            }

            $results['processed']++;
        }

        if (!empty($equipmentBatch)) {
            Log::info("Начало массовой вставки данных", [
                'equipment_count' => count($equipmentBatch),
                'locations_count' => count($locationsMap)
            ]);

            $this->bulkInsertWithRelations($equipmentBatch, $locationsMap, $companyId);

            Log::info("Массовая вставка завершена", [
                'equipment_count' => count($equipmentBatch)
            ]);
        } else {
            Log::warning("Нет данных для массовой вставки");
            throw new \Exception("Нет корректных данных для импорта");
        }

        return $results;
    }

    private function validateRow(array $row, int $companyId): array
    {
        Log::debug("Валидация строки", ['row_data' => $row]);

        $validator = Validator::make($row, [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:equipment_categories,id',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'hours_worked' => 'required|numeric|min:0',
            'price_per_hour' => 'required|numeric|min:0',
            'location_name' => 'required|string|max:255',
            'location_address' => 'required|string|max:500',
            'weight' => 'required|numeric|min:0',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
        ], [
            'title.required' => 'Название техники обязательно для заполнения',
            'description.required' => 'Описание обязательно для заполнения',
            'category_id.required' => 'ID категории обязателен для заполнения',
            'category_id.exists' => 'Категория с указанным ID не существует',
            'brand.required' => 'Бренд обязателен для заполнения',
            'model.required' => 'Модель обязательна для заполнения',
            'year.required' => 'Год выпуска обязателен для заполнения',
            'year.min' => 'Год выпуска не может быть меньше 1900',
            'year.max' => 'Год выпуска не может быть больше ' . (date('Y') + 1),
            'hours_worked.required' => 'Наработка в часах обязательна для заполнения',
            'price_per_hour.required' => 'Цена за час обязательна для заполнения',
            'location_name.required' => 'Название локации обязательно для заполнения',
            'location_address.required' => 'Адрес локации обязателен для заполнения',
            'weight.required' => 'Вес обязателен для заполнения',
            'length.required' => 'Длина обязательна для заполнения',
            'width.required' => 'Ширина обязательна для заполнения',
            'height.required' => 'Высота обязательна для заполнения',
        ]);

        if ($validator->fails()) {
            Log::warning("Валидация не пройдена", [
                'errors' => $validator->errors()->toArray(),
                'data' => $row
            ]);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function bulkInsertWithRelations(array $equipmentBatch, array $locationsMap, int $companyId): void
    {
        DB::transaction(function () use ($equipmentBatch, $locationsMap, $companyId) {
            Log::info("Начало создания локаций", ['locations_count' => count($locationsMap)]);

            // 1. Создаем локации
            $createdLocations = [];
            foreach ($locationsMap as $slug => $locationData) {
                $location = Location::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'name' => $locationData['name'],
                        'address' => $locationData['address']
                    ],
                    [
                        'latitude' => 0.0,
                        'longitude' => 0.0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $createdLocations[$slug] = $location->id;
                Log::debug("Локация создана/найдена", [
                    'slug' => $slug,
                    'location_id' => $location->id,
                    'name' => $locationData['name']
                ]);
            }

            Log::info("Локации обработаны", ['created_locations' => $createdLocations]);

            // 2. Обновляем equipmentBatch с location_id
            foreach ($equipmentBatch as &$equipment) {
                $equipment['location_id'] = $createdLocations[$equipment['slug']] ?? null;
                if (!$equipment['location_id']) {
                    Log::warning("Не найдена локация для оборудования", [
                        'slug' => $equipment['slug'],
                        'title' => $equipment['title']
                    ]);
                    throw new \Exception("Не удалось создать локацию для оборудования: " . $equipment['title']);
                }
            }
            unset($equipment);

            Log::info("Начало массовой вставки оборудования", ['equipment_count' => count($equipmentBatch)]);

            // 3. Массовая вставка оборудования
            $insertedEquipment = [];
            foreach (array_chunk($equipmentBatch, 50) as $chunkIndex => $chunk) {
                Log::debug("Вставка чанка оборудования", [
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => count($chunk)
                ]);

                DB::table('equipment')->insert($chunk);

                // Получаем ID последних вставленных записей
                $lastInserted = Equipment::where('company_id', $companyId)
                    ->orderBy('id', 'desc')
                    ->limit(count($chunk))
                    ->get(['id', 'slug']);

                Log::debug("Получены ID вставленного оборудования", [
                    'chunk_index' => $chunkIndex,
                    'inserted_count' => $lastInserted->count()
                ]);

                foreach ($lastInserted as $equipment) {
                    $insertedEquipment[$equipment->slug] = $equipment->id;
                }
            }

            Log::info("Оборудование вставлено", [
                'total_inserted' => count($insertedEquipment),
                'inserted_slugs' => array_keys($insertedEquipment)
            ]);

            // 4. Создаем связанные записи
            Log::info("Начало создания спецификаций и тарифов");
            $this->createSpecifications($equipmentBatch, $insertedEquipment, $locationsMap);
            $this->createRentalTerms($equipmentBatch, $insertedEquipment, $locationsMap);
            Log::info("Связанные записи созданы");
        });
    }

    private function createSpecifications(array $equipmentBatch, array $equipmentIds, array $locationsMap): void
    {
        $specificationsBatch = [];
        $now = now();

        Log::info("Создание спецификаций", ['equipment_count' => count($equipmentBatch)]);

        foreach ($equipmentBatch as $equipment) {
            $equipmentId = $equipmentIds[$equipment['slug']] ?? null;
            if (!$equipmentId) {
                Log::warning("Не найден ID оборудования для спецификаций", [
                    'slug' => $equipment['slug'],
                    'title' => $equipment['title']
                ]);
                continue;
            }

            $locationData = $locationsMap[$equipment['slug']] ?? [];

            // ИСПРАВЛЕНИЕ: Создаем отдельные записи для каждой спецификации с правильным порядком полей
            $specs = [
                [
                    'equipment_id' => $equipmentId,
                    'key' => 'weight',
                    'value' => (string)($locationData['weight'] ?? 0),
                    'weight' => $locationData['weight'] ?? 0,
                    'length' => null,
                    'width' => null,
                    'height' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'equipment_id' => $equipmentId,
                    'key' => 'length',
                    'value' => (string)($locationData['length'] ?? 0),
                    'weight' => null,
                    'length' => $locationData['length'] ?? 0,
                    'width' => null,
                    'height' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'equipment_id' => $equipmentId,
                    'key' => 'width',
                    'value' => (string)($locationData['width'] ?? 0),
                    'weight' => null,
                    'length' => null,
                    'width' => $locationData['width'] ?? 0,
                    'height' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'equipment_id' => $equipmentId,
                    'key' => 'height',
                    'value' => (string)($locationData['height'] ?? 0),
                    'weight' => null,
                    'length' => null,
                    'width' => null,
                    'height' => $locationData['height'] ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            ];

            foreach ($specs as $spec) {
                $specificationsBatch[] = $spec;
            }
        }

        Log::info("Подготовлены спецификации для вставки", ['specifications_count' => count($specificationsBatch)]);

        foreach (array_chunk($specificationsBatch, 100) as $chunkIndex => $chunk) {
            // ИСПРАВЛЕНИЕ: Используем правильное название таблицы equipment_specifications
            DB::table('equipment_specifications')->insert($chunk);
            Log::debug("Вставлен чанк спецификаций", [
                'chunk_index' => $chunkIndex,
                'specifications_count' => count($chunk)
            ]);
        }
    }

    private function createRentalTerms(array $equipmentBatch, array $equipmentIds, array $locationsMap): void
    {
        $rentalTermsBatch = [];
        $now = now();

        Log::info("Создание тарифов", ['equipment_count' => count($equipmentBatch)]);

        foreach ($equipmentBatch as $equipment) {
            $equipmentId = $equipmentIds[$equipment['slug']] ?? null;
            if (!$equipmentId) {
                Log::warning("Не найден ID оборудования для тарифов", [
                    'slug' => $equipment['slug'],
                    'title' => $equipment['title']
                ]);
                continue;
            }

            $locationData = $locationsMap[$equipment['slug']] ?? [];

            $rentalTermsBatch[] = [
                'equipment_id' => $equipmentId,
                'price_per_hour' => $locationData['price_per_hour'] ?? 0,
                'currency' => 'RUB',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Log::info("Подготовлены тарифы для вставки", ['rental_terms_count' => count($rentalTermsBatch)]);

        foreach (array_chunk($rentalTermsBatch, 100) as $chunkIndex => $chunk) {
            DB::table('equipment_rental_terms')->insert($chunk);
            Log::debug("Вставлен чанк тарифов", [
                'chunk_index' => $chunkIndex,
                'rental_terms_count' => count($chunk)
            ]);
        }
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (Equipment::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        Log::debug("Сгенерирован уникальный slug", [
            'title' => $title,
            'slug' => $slug,
            'iterations' => $counter - 1
        ]);

        return $slug;
    }
}
