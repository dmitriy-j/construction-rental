<?php

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\RentalRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentalRequestService
{
    public function createRentalRequest(array $data, User $user): RentalRequest
    {
        return DB::transaction(function () use ($data, $user) {
            // 🔥 ДОБАВЛЕНО: Логирование входящих данных
            Log::debug('🚚 RentalRequestService - CREATE DATA', [
                'delivery_required' => $data['delivery_required'] ?? 'not_set',
                'delivery_required_type' => isset($data['delivery_required']) ? gettype($data['delivery_required']) : 'not_set',
                'all_data_keys' => array_keys($data)
            ]);

            $requestData = $this->prepareRequestData($data, $user);
            $rentalRequest = RentalRequest::create($requestData);

            // 🔥 ПРОВЕРКА СОХРАНЕННЫХ ДАННЫХ
            Log::debug('✅ RentalRequestService - REQUEST CREATED', [
                'request_id' => $rentalRequest->id,
                'delivery_required_saved' => $rentalRequest->delivery_required,
                'delivery_required_raw' => $rentalRequest->getRawOriginal('delivery_required')
            ]);

            $this->createRequestItems($rentalRequest, $data['items']);
            $rentalRequest->load('items');
            $rentalRequest->calculateBudget();

            Log::info('Rental request created successfully with new structure', [
                'request_id' => $rentalRequest->id,
                'items_count' => $rentalRequest->items->count(),
                'delivery_required_final' => $rentalRequest->delivery_required,
                'items_with_standard_specs' => $rentalRequest->items->filter(fn($item) => !empty($item->standard_specifications))->count(),
                'items_with_custom_specs' => $rentalRequest->items->filter(fn($item) => !empty($item->custom_specifications))->count()
            ]);

            return $rentalRequest->refresh();
        });
    }

    private function prepareRequestData(array $data, User $user): array
    {
        // 🔥 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Правильная обработка delivery_required
        $deliveryRequired = $data['delivery_required'] ?? false;

        // Преобразуем разные форматы в boolean
        if ($deliveryRequired === 'true' || $deliveryRequired === '1' || $deliveryRequired === 1) {
            $deliveryRequired = true;
        } elseif ($deliveryRequired === 'false' || $deliveryRequired === '0' || $deliveryRequired === 0) {
            $deliveryRequired = false;
        }

        Log::debug('🔧 prepareRequestData - delivery_required processing', [
            'original' => $data['delivery_required'] ?? 'not_set',
            'processed' => $deliveryRequired,
            'type' => gettype($deliveryRequired)
        ]);

        return [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'title' => $data['title'],
            'description' => $data['description'],
            'hourly_rate' => (float) $data['hourly_rate'],
            'rental_conditions' => $data['rental_conditions'] ?? $this->getDefaultRentalConditions(),
            'rental_period_start' => $data['rental_period_start'],
            'rental_period_end' => $data['rental_period_end'],
            'location_id' => $data['location_id'],
            // 🔥 ИСПРАВЛЕНИЕ: Используем обработанное значение
            'delivery_required' => $deliveryRequired,
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            // Временные значения
            'total_budget' => 0,
            'calculated_budget_from' => 0,
            'calculated_budget_to' => 0,
            'total_equipment_quantity' => 0
        ];
    }

    private function createRequestItems(RentalRequest $rentalRequest, array $items): void
    {
        foreach ($items as $itemData) {
            $item = $this->prepareItemData($itemData, $rentalRequest->id);

            Log::debug('🔧 Creating rental request item with specs', [
                'category_id' => $itemData['category_id'],
                'standard_specs_count' => count($item['standard_specifications'] ?? []),
                'custom_specs_count' => count($item['custom_specifications'] ?? []),
                'has_standard_specs' => !empty($item['standard_specifications']),
                'has_custom_specs' => !empty($item['custom_specifications'])
            ]);

            RentalRequestItem::create($item);
        }

        $rentalRequest->load('items');
    }

    /**
     * ✅ НОВЫЙ МЕТОД: Подготовка данных позиции с новой структурой спецификаций
     */
    private function prepareItemData(array $itemData, int $rentalRequestId): array
    {
        $item = [
            'rental_request_id' => $rentalRequestId,
            'category_id' => $itemData['category_id'],
            'quantity' => $itemData['quantity'],
            'hourly_rate' => $itemData['hourly_rate'] ?? null,
            'use_individual_conditions' => $itemData['use_individual_conditions'] ?? false,
            'individual_conditions' => $itemData['individual_conditions'] ?? null,
        ];

        try {
            $standardSpecs = $itemData['standard_specifications'] ?? [];
            $customSpecs = $itemData['custom_specifications'] ?? [];

            Log::debug('🔧 Preparing specifications', [
                'standard_specs_count' => count($standardSpecs),
                'custom_specs_count' => count($customSpecs),
                'standard_keys' => array_keys($standardSpecs),
                'custom_keys' => array_keys($customSpecs)
            ]);

            // Обрабатываем стандартные спецификации
            $item['standard_specifications'] = $this->processStandardSpecifications($standardSpecs);

            // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Обрабатываем кастомные спецификации с защитой от null
            $item['custom_specifications'] = $this->processCustomSpecifications($customSpecs);

            // Метаданные для кастомных спецификаций
            $customMetadata = [];
            foreach ($item['custom_specifications'] as $key => $customSpec) {
                $customMetadata[$key] = [
                    'name' => $customSpec['label'] ?? $key,
                    'dataType' => $customSpec['dataType'] ?? 'string',
                    'unit' => $customSpec['unit'] ?? ''
                ];
            }
            $item['custom_specs_metadata'] = $customMetadata;

            // 🔄 Для обратной совместимости
            $legacySpecs = array_merge(
                $item['standard_specifications'],
                $this->extractCustomSpecValues($item['custom_specifications'])
            );
            $item['specifications'] = $legacySpecs;

            Log::debug('✅ Specifications prepared successfully', [
                'final_standard_count' => count($item['standard_specifications']),
                'final_custom_count' => count($item['custom_specifications']),
                'final_legacy_count' => count($item['specifications'])
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error preparing specifications: ' . $e->getMessage(), [
                'item_data' => $itemData
            ]);

            // Устанавливаем пустые спецификации в случае ошибки
            $item['standard_specifications'] = [];
            $item['custom_specifications'] = [];
            $item['specifications'] = [];
            $item['custom_specs_metadata'] = [];
        }

        return $item;
    }

    // ✅ НОВЫЙ МЕТОД: Безопасная обработка стандартных спецификаций
    private function processStandardSpecifications(array $specs): array
    {
        $processed = [];

        foreach ($specs as $key => $value) {
            try {
                if ($value === '' || $value === null) {
                    continue; // Пропускаем пустые значения
                }

                // Преобразуем в число если возможно
                if (is_numeric($value)) {
                    $processed[$key] = (float) $value;
                } elseif (is_string($value) && str_contains($value, ',')) {
                    $normalized = str_replace(',', '.', $value);
                    $processed[$key] = is_numeric($normalized) ? (float) $normalized : $value;
                } else {
                    $processed[$key] = $value;
                }
            } catch (\Exception $e) {
                Log::warning("Error processing standard spec {$key}", [
                    'value' => $value,
                    'error' => $e->getMessage()
                ]);
                $processed[$key] = $value; // Сохраняем оригинальное значение
            }
        }

        return $processed;
    }

    // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Безопасная обработка кастомных спецификаций с защитой от null
    private function processCustomSpecifications(array $specs): array
    {
        $processed = [];

        foreach ($specs as $key => $spec) {
            try {
                if (!is_array($spec)) {
                    // Если это просто значение, преобразуем в структуру
                    $processed[$key] = [
                        'label' => $this->formatLabel($key),
                        'value' => $spec,
                        'unit' => '', // ✅ Гарантируем пустую строку вместо null
                        'dataType' => is_numeric($spec) ? 'number' : 'string'
                    ];
                    continue;
                }

                // Обрабатываем структурированную спецификацию
                $value = $spec['value'] ?? '';

                if ($value === '' || $value === null) {
                    continue; // Пропускаем пустые
                }

                // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Гарантируем что unit всегда строка
                $unitValue = '';
                if (isset($spec['unit']) && $spec['unit'] !== null) {
                    $unitValue = (string) $spec['unit'];
                }

                // Нормализуем значение
                if ($spec['dataType'] === 'number' || is_numeric($value)) {
                    if (is_string($value) && str_contains($value, ',')) {
                        $value = str_replace(',', '.', $value);
                    }
                    $normalizedValue = is_numeric($value) ? (float) $value : $value;
                } else {
                    $normalizedValue = $value;
                }

                $processed[$key] = [
                    'label' => $spec['label'] ?? $this->formatLabel($key),
                    'value' => $normalizedValue,
                    'unit' => $unitValue, // ✅ Всегда строка
                    'dataType' => $spec['dataType'] ?? (is_numeric($normalizedValue) ? 'number' : 'string')
                ];

            } catch (\Exception $e) {
                Log::warning("Error processing custom spec {$key}", [
                    'spec' => $spec,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processed;
    }

    private function formatLabel(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Извлечение значений из кастомных спецификаций для старой структуры
     */
    private function extractCustomSpecValues(array $customSpecs): array
    {
        $values = [];
        foreach ($customSpecs as $key => $spec) {
            $values[$key] = $spec['value'] ?? '';
        }
        return $values;
    }

    private function getDefaultRentalConditions(): array
    {
        return [
            'payment_type' => 'hourly',
            'hours_per_shift' => 8,
            'shifts_per_day' => 1,
            'transportation_organized_by' => 'lessor',
            'gsm_payment' => 'included',
            'accommodation_payment' => false,
            'extension_possibility' => true,
            'operator_included' => false
        ];
    }

    /**
     * Метод для обновления бюджета существующей заявки
     */
    public function updateRentalRequestBudget(RentalRequest $rentalRequest): void
    {
        $rentalRequest->load('items');
        $rentalRequest->calculateBudget();
    }

    public function updateRentalRequest(RentalRequest $rentalRequest, array $validatedData)
    {
        DB::beginTransaction();

        try {
            Log::info('🔧 RentalRequestService: update started with new structure', [
                'request_id' => $rentalRequest->id,
                'items_count' => count($validatedData['items'] ?? []),
                'has_standard_specs' => !empty($validatedData['items'][0]['standard_specifications'] ?? []),
                'has_custom_specs' => !empty($validatedData['items'][0]['custom_specifications'] ?? [])
            ]);

            // Обновление основной информации заявки
            $rentalRequest->update([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'hourly_rate' => $validatedData['hourly_rate'],
                'rental_period_start' => $validatedData['rental_period_start'],
                'rental_period_end' => $validatedData['rental_period_end'],
                'location_id' => $validatedData['location_id'],
                'rental_conditions' => $validatedData['rental_conditions'] ?? [],
                'delivery_required' => $validatedData['delivery_required'] ?? false,
            ]);

            // Удаляем существующие позиции и создаем новые
            $rentalRequest->items()->delete();

            foreach ($validatedData['items'] as $itemData) {
                Log::debug('🔧 Creating/updating item with new specs structure', [
                    'category_id' => $itemData['category_id'],
                    'standard_specs_count' => count($itemData['standard_specifications'] ?? []),
                    'custom_specs_count' => count($itemData['custom_specifications'] ?? [])
                ]);

                // ✅ ИСПРАВЛЕНИЕ: Используем новый метод подготовки данных
                $item = $this->prepareItemData($itemData, $rentalRequest->id);

                $rentalRequest->items()->create($item);
            }

            // Пересчитываем и сохраняем общий бюджет
            $rentalRequest->calculateBudget();
            $rentalRequest->save();

            DB::commit();

            // Загружаем обновленные отношения
            $updatedRequest = $rentalRequest->fresh(['items.category', 'location']);

            Log::info('✅ RentalRequestService: update completed with new structure', [
                'request_id' => $updatedRequest->id,
                'items_count' => $updatedRequest->items->count(),
                'items_with_standard_specs' => $updatedRequest->items->filter(fn($item) => !empty($item->standard_specifications))->count(),
                'items_with_custom_specs' => $updatedRequest->items->filter(fn($item) => !empty($item->custom_specifications))->count(),
                'total_budget' => $updatedRequest->total_budget
            ]);

            return $updatedRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ RentalRequestService update error: ' . $e->getMessage(), [
                'request_id' => $rentalRequest->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Полностью переписанный метод форматирования спецификаций
    public function formatSpecifications($specifications, $metadata = []) {
        if (empty($specifications)) {
            return [];
        }

        \Log::debug('🎯 MAIN formatSpecifications CALLED', [
            'specifications_type' => gettype($specifications),
            'specifications_keys' => is_array($specifications) ? array_keys($specifications) : 'not_array',
            'has_standard_specs' => isset($specifications['standard_specifications']),
            'has_custom_specs' => isset($specifications['custom_specifications']),
            'metadata_count' => count($metadata)
        ]);

        $formatted = [];

        try {
            // 1. ОБРАБОТКА СТАНДАРТНЫХ СПЕЦИФИКАЦИЙ
            if (isset($specifications['standard_specifications']) && is_array($specifications['standard_specifications'])) {
                foreach ($specifications['standard_specifications'] as $key => $value) {
                    if (!is_null($value) && $value !== '') {
                        $formattedSpec = $this->formatSingleSpecification($key, $value);
                        if ($formattedSpec) {
                            $formatted[] = $formattedSpec;
                        }
                    }
                }
                \Log::debug('✅ Обработаны стандартные спецификации', ['count' => count($formatted)]);
            }

            // 2. ОБРАБОТКА КАСТОМНЫХ СПЕЦИФИКАЦИЙ С МЕТАДАННЫМИ
            if (isset($specifications['custom_specifications']) && is_array($specifications['custom_specifications'])) {
                foreach ($specifications['custom_specifications'] as $customKey => $customData) {
                    // ✅ ИСПРАВЛЕНИЕ: Используем метаданные для получения человекочитаемых названий
                    $customMeta = $metadata[$customKey] ?? [];

                    \Log::debug('🔧 Обработка кастомной спецификации с метаданными', [
                        'custom_key' => $customKey,
                        'custom_data' => $customData,
                        'metadata' => $customMeta
                    ]);

                    if (is_array($customData)) {
                        // Структурированный формат с label, value, unit
                        if (isset($customData['value'])) {
                            $value = $customData['value'];
                            // ✅ ИСПРАВЛЕНИЕ: Берем label из метаданных или из данных
                            $label = $customMeta['name'] ?? $customData['label'] ?? $this->formatLabel($customKey);
                            $unit = $customMeta['unit'] ?? $customData['unit'] ?? '';

                            if (empty($value) && $value !== 0 && $value !== '0') {
                                continue;
                            }

                            $displayValue = $this->formatDisplayValue($value, $unit);

                            $formatted[] = [
                                'key' => $customKey,
                                'label' => $label,
                                'value' => $value,
                                'unit' => $unit,
                                'display_value' => $displayValue,
                                'formatted' => $label . ': ' . $displayValue,
                                'is_custom' => true,
                                'data_type' => $customMeta['dataType'] ?? $customData['dataType'] ?? 'string'
                            ];
                        }
                    } else {
                        // Простой формат - только значение
                        $value = $customData;
                        if (empty($value) && $value !== 0 && $value !== '0') {
                            continue;
                        }

                        // ✅ ИСПРАВЛЕНИЕ: Берем название из метаданных
                        $label = $customMeta['name'] ?? $this->formatLabel($customKey);
                        $unit = $customMeta['unit'] ?? '';
                        $displayValue = $this->formatDisplayValue($value, $unit);

                        $formatted[] = [
                            'key' => $customKey,
                            'label' => $label,
                            'value' => $value,
                            'unit' => $unit,
                            'display_value' => $displayValue,
                            'formatted' => $label . ': ' . $displayValue,
                            'is_custom' => true,
                            'data_type' => $customMeta['dataType'] ?? (is_numeric($value) ? 'number' : 'string')
                        ];
                    }

                    \Log::debug('✅ Кастомная спецификация отформатирована', [
                        'key' => $customKey,
                        'label' => $label ?? 'unknown',
                        'value' => $value ?? 'unknown'
                    ]);
                }
                \Log::debug('✅ Обработаны кастомные спецификации', ['count' => count($formatted)]);
            }

            // 3. ОБРАБОТКА СТАРОЙ СТРУКТУРЫ (для обратной совместимости)
            if (empty($formatted) && !isset($specifications['standard_specifications']) && !isset($specifications['custom_specifications'])) {
                \Log::debug('🔄 Используется старая структура спецификаций');
                foreach ($specifications as $key => $value) {
                    if ($key === 'metadata') continue;

                    if (!is_array($value) && !is_null($value) && $value !== '') {
                        // ✅ ИСПРАВЛЕНИЕ: Для кастомных спецификаций в старой структуре используем метаданные
                        if (str_starts_with($key, 'custom_')) {
                            $customMeta = $metadata[$key] ?? [];
                            $label = $customMeta['name'] ?? $this->formatLabel($key);
                            $unit = $customMeta['unit'] ?? '';
                            $displayValue = $this->formatDisplayValue($value, $unit);

                            $formatted[] = [
                                'key' => $key,
                                'label' => $label,
                                'value' => $value,
                                'unit' => $unit,
                                'display_value' => $displayValue,
                                'formatted' => $label . ': ' . $displayValue,
                                'is_custom' => true,
                                'data_type' => $customMeta['dataType'] ?? (is_numeric($value) ? 'number' : 'string')
                            ];
                        } else {
                            $formattedSpec = $this->formatSingleSpecification($key, $value);
                            if ($formattedSpec) {
                                $formatted[] = $formattedSpec;
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('❌ Критическая ошибка при форматировании спецификаций', [
                'error' => $e->getMessage(),
                'specifications' => $specifications,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }

        \Log::debug('🎯 Форматирование завершено', [
            'total_specs' => count($formatted),
            'standard_specs' => count(array_filter($formatted, fn($spec) => !$spec['is_custom'])),
            'custom_specs' => count(array_filter($formatted, fn($spec) => $spec['is_custom'])),
            'custom_specs_sample' => array_slice(array_filter($formatted, fn($spec) => $spec['is_custom']), 0, 3)
        ]);

        return $formatted;
    }

    private function formatSingleSpecification($key, $value)
    {
        \Log::debug("🔧 formatSingleSpecification CALLED", [
            'key' => $key,
            'value' => $value,
            'is_weight' => $key === 'weight' || $key === 'weigh',
            'key_type' => gettype($key)
        ]);

        // ПРЕОБРАЗОВАНИЕ МАССИВА В СТРОКУ если необходимо
        if (is_array($value)) {
            $value = implode(', ', array_filter($value, function($item) {
                return !is_null($item) && $item !== '';
            }));
        }

        // Если после преобразования значение пустое - пропускаем
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        // ✅ ИСПРАВЛЕНИЕ: УБИРАЕМ ПРОПУСК КАСТОМНЫХ СПЕЦИФИКАЦИЙ
        // Кастомные спецификации теперь обрабатываются на уровне formatSpecifications
        // и не должны попадать в этот метод для отдельных ключей

        $labelMappings = [
            'bucket_volume' => 'Объем ковша',
            'operating_weight' => 'Рабочий вес',
            'body_volume' => 'Объем кузова',
            'load_capacity' => 'Грузоподъемность',
            'power' => 'Мощность',
            'weight' => 'Вес',
            'weigh' => 'Вес',
            'max_speed' => 'Максимальная скорость',
            'axle_configuration' => 'Колесная формула',
            'digging_depth' => 'Глубина копания',
            'blade_width' => 'Ширина отвала',
            'blade_height' => 'Высота отвала',
            'max_digging_depth' => 'Макс. глубина копания',
            'engine_power' => 'Мощность двигателя',
            'engine_type' => 'Тип двигателя',
            'fuel_type' => 'Тип топлива',
            'transmission' => 'Трансмиссия',
            'bucket_capacity' => 'Емкость ковша',
            'boom_length' => 'Длина стрелы',
            'arm_length' => 'Длина рукояти',
            'lifting_capacity' => 'Грузоподъемность',
            'max_reach' => 'Макс. вылет',
            'max_height' => 'Макс. высота',
            'track_width' => 'Ширина гусениц',
            'ground_clearance' => 'Дорожный просвет',
            'tire_size' => 'Размер шин',
            'hydraulic_system' => 'Гидравлическая система',
            'operator_cabin' => 'Кабина оператора',
            'air_conditioning' => 'Кондиционер',
            'radio' => 'Радио',
            'safety_systems' => 'Системы безопасности'
        ];

        $unitMappings = [
            'bucket_volume' => 'м³',
            'operating_weight' => 'т',
            'body_volume' => 'м³',
            'load_capacity' => 'т',
            'power' => 'л.с.',
            'weight' => 'т',
            'weigh' => 'т',
            'max_speed' => 'км/ч',
            'digging_depth' => 'м',
            'blade_width' => 'м',
            'blade_height' => 'м',
            'max_digging_depth' => 'м',
            'engine_power' => 'кВт',
            'boom_length' => 'м',
            'arm_length' => 'м',
            'lifting_capacity' => 'т',
            'max_reach' => 'м',
            'max_height' => 'м',
            'track_width' => 'м',
            'ground_clearance' => 'м',
            'tire_size' => 'дюймов'
        ];

        $label = $labelMappings[$key] ?? $key;
        $unit = $unitMappings[$key] ?? '';

        $displayValue = $this->formatDisplayValue($value, $unit);

        $result = [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'unit' => $unit,
            'display_value' => $displayValue,
            'formatted' => $label . ': ' . $displayValue,
            'is_custom' => false,
            'data_type' => gettype($value)
        ];

        \Log::debug("🔧 formatSingleSpecification RESULT", [
            'key' => $key,
            'label' => $label,
            'result' => $result
        ]);

        return $result;
    }

    private function formatDisplayValue($value, $unit) {
        if (is_null($value) || $value === '') {
            return 'не указано';
        }

        // Для числовых значений убираем лишние нули
        if (is_numeric($value)) {
            $value = (float)$value;
            if ($value == (int)$value) {
                $value = (int)$value;
            }
        }

        return $value . ($unit ? ' ' . $unit : '');
    }

    public function getActiveRequestsForLessor(User $user, array $filters = [], int $perPage = 15)
    {
        $query = RentalRequest::active()
            ->public()
            ->with(['items.category', 'location']);

        // Применяем фильтры
        if (!empty($filters['category_id'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['budget_max'])) {
            $query->where('total_budget', '<=', $filters['budget_max']);
        }

        // Сортировка
        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'budget':
                    $query->orderBy('total_budget', 'desc');
                    break;
                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Создание заявки через API с поддержкой метаданных
     */
    public function createRentalRequestFromApi(array $data, User $user): RentalRequest
    {
        return DB::transaction(function () use ($data, $user) {
            Log::info('🔧 API RentalRequest creation started', [
                'user_id' => $user->id,
                'items_count' => count($data['items'] ?? [])
            ]);

            $requestData = $this->prepareRequestData($data, $user);
            $rentalRequest = RentalRequest::create($requestData);

            // Создаем items с метаданными
            foreach ($data['items'] as $itemData) {
                Log::debug('🔧 API Creating item with metadata', [
                    'category_id' => $itemData['category_id'],
                    'specifications_count' => count($itemData['specifications'] ?? []),
                    'metadata_count' => count($itemData['custom_specs_metadata'] ?? [])
                ]);

                $item = $this->prepareItemData($itemData, $rentalRequest->id);

                $rentalRequest->items()->create($item);
            }

            $rentalRequest->load('items');
            $rentalRequest->calculateBudget();

            Log::info('✅ API Rental request created successfully', [
                'request_id' => $rentalRequest->id,
                'total_budget' => $rentalRequest->total_budget,
                'items_count' => $rentalRequest->items->count(),
                'items_with_standard_specs' => $rentalRequest->items->filter(fn($item) => !empty($item->standard_specifications))->count(),
                'items_with_custom_specs' => $rentalRequest->items->filter(fn($item) => !empty($item->custom_specifications))->count()
            ]);

            return $rentalRequest->refresh();
        });
    }
}
