<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\EquipmentSpecificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SpecificationController extends Controller
{
    protected $specificationService;

    public function __construct(EquipmentSpecificationService $specificationService)
    {
        $this->specificationService = $specificationService;
    }

    /**
     * Get specification template for category (новый метод для фронтенда)
     */
    public function getTemplateByCategory($categoryId): JsonResponse
    {
        try {
            \Log::info('🔧 API: Получение шаблона спецификаций для категории', ['category_id' => $categoryId]);

            // Проверяем существование категории
            $category = Category::find($categoryId);
            if (!$category) {
                \Log::warning('❌ Категория не найдена', ['category_id' => $categoryId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Категория не найдена',
                    'template' => []
                ], 404);
            }

            // Получаем стандартные спецификации для категории
            $standardSpecs = $this->getStandardSpecsForCategory($categoryId);

            // Формируем ответ в формате, ожидаемом фронтендом
            $template = [
                'success' => true,
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name
                    ],
                    'standard_specifications' => $standardSpecs
                ]
            ];

            \Log::info('✅ API: Шаблон успешно загружен', [
                'category_id' => $categoryId,
                'specs_count' => count($standardSpecs)
            ]);

            return response()->json($template);

        } catch (\Exception $e) {
            \Log::error('❌ API: Ошибка загрузки шаблона спецификаций', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке шаблона параметров',
                'template' => []
            ], 500);
        }
    }

    /**
     * Получение стандартных спецификаций для категории
     */
    private function getStandardSpecsForCategory($categoryId): array
    {
        // Базовые спецификации для всех категорий
        $commonSpecs = [
            [
                'key' => 'weight',
                'label' => 'Вес',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => 'Введите значение в т'
            ],
            [
                'key' => 'power',
                'label' => 'Мощность',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => 'Введите значение в л.с.'
            ],
            [
                'key' => 'max_speed',
                'label' => 'Максимальная скорость',
                'unit' => 'км/ч',
                'type' => 'number',
                'placeholder' => 'Введите значение в км/ч'
            ]
        ];

        // Специфические спецификации по категориям
        $categorySpecificSpecs = [
            // Экскаваторы (ID 1)
            1 => [
                [
                    'key' => 'bucket_volume',
                    'label' => 'Объем ковша',
                    'unit' => 'м³',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м³'
                ],
                [
                    'key' => 'digging_depth',
                    'label' => 'Глубина копания',
                    'unit' => 'м',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м'
                ],
                [
                    'key' => 'max_reach',
                    'label' => 'Максимальный вылет',
                    'unit' => 'м',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м'
                ]
            ],
            // Самосвалы (ID 3)
            3 => [
                [
                    'key' => 'load_capacity',
                    'label' => 'Грузоподъемность',
                    'unit' => 'т',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в т'
                ],
                [
                    'key' => 'body_volume',
                    'label' => 'Объем кузова',
                    'unit' => 'м³',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м³'
                ],
                [
                    'key' => 'axle_configuration',
                    'label' => 'Колёсная формула',
                    'unit' => '',
                    'type' => 'text',
                    'placeholder' => 'Например: 6x4, 8x4'
                ]
            ],
            // Грузовики (ID 10)
            10 => [
                [
                    'key' => 'load_capacity',
                    'label' => 'Грузоподъемность',
                    'unit' => 'т',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в т'
                ],
                [
                    'key' => 'body_volume',
                    'label' => 'Объем кузова',
                    'unit' => 'м³',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м³'
                ],
                [
                    'key' => 'axle_configuration',
                    'label' => 'Колёсная формула',
                    'unit' => '',
                    'type' => 'text',
                    'placeholder' => 'Например: 6x4, 8x4'
                ]
            ],
            // Бульдозеры (ID 2)
            2 => [
                [
                    'key' => 'blade_width',
                    'label' => 'Ширина отвала',
                    'unit' => 'м',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м'
                ],
                [
                    'key' => 'blade_height',
                    'label' => 'Высота отвала',
                    'unit' => 'м',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м'
                ],
                [
                    'key' => 'blade_capacity',
                    'label' => 'Объем отвала',
                    'unit' => 'м³',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м³'
                ]
            ],
            // Краны (ID 4)
            4 => [
                [
                    'key' => 'lifting_capacity',
                    'label' => 'Грузоподъемность',
                    'unit' => 'т',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в т'
                ],
                [
                    'key' => 'boom_length',
                    'label' => 'Длина стрелы',
                    'unit' => 'м',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м'
                ],
                [
                    'key' => 'max_lifting_height',
                    'label' => 'Максимальная высота подъема',
                    'unit' => 'м',
                    'type' => 'number',
                    'placeholder' => 'Введите значение в м'
                ]
            ]
        ];

        // Объединяем общие и специфические спецификации
        $specificSpecs = $categorySpecificSpecs[$categoryId] ?? [];
        return array_merge($commonSpecs, $specificSpecs);
    }

    /**
     * Get specification template for category (старый метод - для обратной совместимости)
     */
    public function getTemplate($categoryId): JsonResponse
    {
        try {
            $category = Category::find($categoryId);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Категория не найдена',
                    'template' => []
                ], 404);
            }

            // Получаем шаблон в виде ассоциативного массива
            $templateData = $this->specificationService->getTemplateForCategoryId($categoryId);

            // Словарь переводов для стандартных параметров
            $russianTranslations = [
                // Общие параметры
                'engine_power' => 'Мощность двигателя',
                'operating_weight' => 'Рабочий вес',
                'max_speed' => 'Максимальная скорость',
                'fuel_tank_capacity' => 'Объем топливного бака',
                'transmission' => 'Трансмиссия',
                'drive_type' => 'Тип привода',

                // Экскаваторы
                'bucket_volume' => 'Объем ковша',
                'max_digging_depth' => 'Максимальная глубина копания',
                'max_reach' => 'Максимальный вылет стрелы',
                'bucket_width' => 'Ширина ковша',
                'arm_force' => 'Усилие на рукояти',
                'boom_force' => 'Усилие на стреле',

                // Бульдозеры
                'blade_width' => 'Ширина отвала',
                'blade_height' => 'Высота отвала',
                'blade_capacity' => 'Объем отвала',
                'max_cutting_depth' => 'Максимальная глубина резания',
                'max_lifting_height' => 'Максимальная высота подъема',

                // Самосвалы
                'load_capacity' => 'Грузоподъемность',
                'body_volume' => 'Объем кузова',
                'body_length' => 'Длина кузова',
                'body_width' => 'Ширина кузова',
                'body_height' => 'Высота кузова',
                'unloading_angle' => 'Угол разгрузки',
                'axle_configuration' => 'Колёсная формула',

                // Краны
                'lifting_capacity' => 'Грузоподъёмность',
                'boom_length' => 'Длина стрелы',
                'max_lifting_height' => 'Максимальная высота подъема',
                'outreach' => 'Вылет стрелы',
                'rotation_angle' => 'Угол поворота',

                // Катки
                'roller_width' => 'Ширина вальца',
                'roller_diameter' => 'Диаметр вальца',
                'vibration_frequency' => 'Частота вибрации',
                'amplitude' => 'Амплитуда',
                'compaction_width' => 'Ширина уплотнения',

                // English variants
                'Bucket volume' => 'Объем ковша',
                'Engine power' => 'Мощность двигателя',
                'Operating weight' => 'Рабочий вес',
                'Max digging depth' => 'Максимальная глубина копания',
                'Blade width' => 'Ширина отвала',
                'Blade height' => 'Высота отвала',
                'Load capacity' => 'Грузоподъемность',
                'Body volume' => 'Объем кузова',
                'Max speed' => 'Максимальная скорость',
                'Lifting capacity' => 'Грузоподъёмность',
                'Boom length' => 'Длина стрелы',
                'Max lifting height' => 'Максимальная высота подъема'
            ];

            // Преобразуем ассоциативный массив в индексированный массив объектов
            $templateArray = [];
            foreach ($templateData as $parameterKey => $parameterConfig) {
                // Определяем русский label
                $originalLabel = $parameterConfig['label'] ?? $parameterKey;
                $russianLabel = $russianTranslations[$parameterKey] ??
                            $russianTranslations[$originalLabel] ??
                            $originalLabel;

                // Определяем единицы измерения
                $unit = $parameterConfig['unit'] ?? '';
                if (empty($unit)) {
                    // Автоматическое определение единиц измерения по типу параметра
                    $unit = $this->getUnitForParameter($parameterKey);
                }

                // Определяем placeholder
                $placeholder = $parameterConfig['placeholder'] ?? '';
                if (empty($placeholder)) {
                    $placeholder = $this->getPlaceholderForParameter($parameterKey, $unit);
                }

                $templateArray[] = [
                    'key' => $parameterKey,
                    'label' => $russianLabel,
                    'unit' => $unit,
                    'type' => $parameterConfig['type'] ?? 'text',
                    'placeholder' => $placeholder,
                    'default' => $parameterConfig['default'] ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'category' => $category->name,
                'slug' => $category->slug,
                'template' => $templateArray
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting specification template', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке шаблона параметров',
                'template' => []
            ], 500);
        }
    }

    private function getUnitForParameter(string $parameterKey): string
    {
        $unitMap = [
            'engine_power' => 'л.с.',
            'operating_weight' => 'т',
            'max_speed' => 'км/ч',
            'fuel_tank_capacity' => 'л',
            'bucket_volume' => 'м³',
            'max_digging_depth' => 'м',
            'max_reach' => 'м',
            'bucket_width' => 'м',
            'arm_force' => 'кН',
            'boom_force' => 'кН',
            'blade_width' => 'м',
            'blade_height' => 'м',
            'blade_capacity' => 'м³',
            'max_cutting_depth' => 'м',
            'max_lifting_height' => 'м',
            'load_capacity' => 'т',
            'body_volume' => 'м³',
            'body_length' => 'м',
            'body_width' => 'м',
            'body_height' => 'м',
            'unloading_angle' => '°',
            'lifting_capacity' => 'т',
            'boom_length' => 'м',
            'outreach' => 'м',
            'rotation_angle' => '°',
            'roller_width' => 'м',
            'roller_diameter' => 'м',
            'vibration_frequency' => 'Гц',
            'amplitude' => 'мм',
            'compaction_width' => 'м'
        ];

        return $unitMap[$parameterKey] ?? '';
    }

    private function getPlaceholderForParameter(string $parameterKey, string $unit): string
    {
        if (!empty($unit)) {
            return "Введите значение в {$unit}";
        }

        $placeholderMap = [
            'transmission' => 'Например: Гидромеханическая',
            'drive_type' => 'Например: Гусеничный, Колесный',
            'axle_configuration' => 'Например: 6x4, 8x4'
        ];

        return $placeholderMap[$parameterKey] ?? 'Введите значение';
    }

    /**
     * Validate specifications
     */
    public function validateSpecifications(): JsonResponse
    {
        try {
            $data = request()->validate([
                'category_id' => 'required|exists:categories,id',
                'specifications' => 'required|array'
            ]);

            $errors = $this->specificationService->validateSpecifications(
                Category::find($data['category_id'])->name,
                $data['specifications']
            );

            return response()->json([
                'success' => true,
                'valid' => empty($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации'
            ], 500);
        }
    }
}
