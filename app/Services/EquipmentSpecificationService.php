<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;

class EquipmentSpecificationService
{
    private $specificationTemplates = [
        'gusenicnyi-ekskavator' => [
            'bucket_volume' => [
                'label' => 'Объем ковша',
                'unit' => 'м³',
                'type' => 'number',
                'placeholder' => '1.8'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '150'
            ],
            'operating_weight' => [
                'label' => 'Эксплуатационный вес',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '20.5'
            ],
            'max_digging_depth' => [
                'label' => 'Макс. глубина копания',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '6.2'
            ]
        ],

        'ekskavator-pogruzcik' => [
            'bucket_volume' => [
                'label' => 'Объем ковша',
                'unit' => 'м³',
                'type' => 'number',
                'placeholder' => '1.2'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '110'
            ],
            'weight' => [
                'label' => 'Вес',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '8.5'
            ],
            'max_digging_depth' => [
                'label' => 'Макс. глубина копания',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '4.0'
            ]
        ],

        'buldozer' => [
            'blade_width' => [
                'label' => 'Ширина отвала',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '4.2'
            ],
            'blade_height' => [
                'label' => 'Высота отвала',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '1.5'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '200'
            ],
            'operating_weight' => [
                'label' => 'Эксплуатационный вес',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '25.0'
            ]
        ],

        'frontalnyi-pogruzcik' => [
            'bucket_volume' => [
                'label' => 'Объем ковша',
                'unit' => 'м³',
                'type' => 'number',
                'placeholder' => '3.0'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '180'
            ],
            'load_capacity' => [
                'label' => 'Грузоподъемность',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '5.0'
            ],
            'breakout_force' => [
                'label' => 'Усилие отрыва',
                'unit' => 'кН',
                'type' => 'number',
                'placeholder' => '120'
            ]
        ],

        'kran' => [
            'load_capacity' => [
                'label' => 'Грузоподъемность',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '25'
            ],
            'boom_length' => [
                'label' => 'Длина стрелы',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '30'
            ],
            'max_lifting_height' => [
                'label' => 'Макс. высота подъема',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '35'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '250'
            ]
        ],

        'manipuliator' => [
            'load_capacity' => [
                'label' => 'Грузоподъемность',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '5.0'
            ],
            'boom_length' => [
                'label' => 'Длина стрелы',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '15'
            ],
            'max_reach' => [
                'label' => 'Макс. вылет стрелы',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '12'
            ],
            'rotation_angle' => [
                'label' => 'Угол поворота',
                'unit' => '°',
                'type' => 'number',
                'placeholder' => '360'
            ]
        ],

        'katok-doroznyi' => [
            'operating_weight' => [
                'label' => 'Эксплуатационный вес',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '10.0'
            ],
            'drum_width' => [
                'label' => 'Ширина вальца',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '2.0'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '130'
            ],
            'vibration_frequency' => [
                'label' => 'Частота вибрации',
                'unit' => 'Гц',
                'type' => 'number',
                'placeholder' => '50'
            ]
        ],

        'avtobetononasos' => [
            'concrete_output' => [
                'label' => 'Производительность по бетону',
                'unit' => 'м³/ч',
                'type' => 'number',
                'placeholder' => '90'
            ],
            'max_pressure' => [
                'label' => 'Макс. давление',
                'unit' => 'бар',
                'type' => 'number',
                'placeholder' => '70'
            ],
            'boom_length' => [
                'label' => 'Длина стрелы',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '40'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '300'
            ]
        ],

        'avtogreider' => [
            'blade_length' => [
                'label' => 'Длина отвала',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '4.0'
            ],
            'blade_height' => [
                'label' => 'Высота отвала',
                'unit' => 'м',
                'type' => 'number',
                'placeholder' => '0.8'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '180'
            ],
            'operating_weight' => [
                'label' => 'Эксплуатационный вес',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '15.0'
            ]
        ],

        'samosval' => [
            'load_capacity' => [
                'label' => 'Грузоподъемность',
                'unit' => 'т',
                'type' => 'number',
                'placeholder' => '25'
            ],
            'body_volume' => [
                'label' => 'Объем кузова',
                'unit' => 'м³',
                'type' => 'number',
                'placeholder' => '15'
            ],
            'engine_power' => [
                'label' => 'Мощность двигателя',
                'unit' => 'л.с.',
                'type' => 'number',
                'placeholder' => '350'
            ],
            'max_speed' => [
                'label' => 'Макс. скорость',
                'unit' => 'км/ч',
                'type' => 'number',
                'placeholder' => '80'
            ]
        ]
    ];

    /**
     * Get template for category ID
     */
    public function getTemplateForCategoryId($categoryId): array
    {
        try {
            $category = Category::find($categoryId);

            if (!$category) {
                \Log::warning("Category not found for ID: {$categoryId}");
                return [];
            }


            // Прямое использование slug из базы данных
            $template = $this->getTemplateForCategory($category->slug);

            return $template;

        } catch (\Exception $e) {
            \Log::error("Error loading template for category {$categoryId}: " . $e->getMessage());
            return [];
        }
    }

     public function formatSpecificationsWithCustom($specifications, $categoryName = ''): array
    {
        if (empty($specifications)) {
            return [];
        }

        try {
            $formatted = [];
            $specsArray = is_array($specifications) ? $specifications : [];

            // Обрабатываем новый формат с labels/values
            if (isset($specsArray['values']) && is_array($specsArray['values'])) {
                $values = $specsArray['values'];
                $labels = $specsArray['labels'] ?? [];

                foreach ($values as $key => $value) {
                    if ($value !== null && $value !== '' && $value !== 'null') {

                        // 🔥 ОСНОВНОЕ ИСПРАВЛЕНИЕ: Используем label из labels если он есть
                        if (isset($labels[$key]) && !empty($labels[$key])) {
                            // Если в labels есть название параметра - используем его
                            $label = $labels[$key];
                            $unit = $this->getUnitForParameter($key);
                            $formatted[] = "{$label}: {$value}{$unit}";
                        }
                        // 🔥 ОБРАБОТКА КАСТОМНЫХ ПАРАМЕТРОВ БЕЗ LABEL
                        else if (strpos($key, 'custom_') === 0 || strpos($key, 'Custom') === 0) {
                            $label = $this->extractCustomParameterName($key);
                            $formatted[] = "{$label}: {$value}";
                        }
                        // СТАНДАРТНЫЕ ПАРАМЕТРЫ
                        else {
                            $formatted[] = $this->formatSpecification($key, $value, $categoryName);
                        }
                    }
                }
            }
            // Старый формат (без labels/values)
            else {
                foreach ($specsArray as $key => $value) {
                    if ($key !== 'labels' && $value !== null && $value !== '') {
                        if (strpos($key, 'custom_') === 0 || strpos($key, 'Custom') === 0) {
                            $label = $this->extractCustomParameterName($key);
                            $formatted[] = "{$label}: {$value}";
                        } else {
                            $formatted[] = $this->formatSpecification($key, $value, $categoryName);
                        }
                    }
                }
            }

            return $formatted;

        } catch (\Exception $e) {
            \Log::error('Error formatting specifications with custom: ' . $e->getMessage());
            return [];
        }
    }

    private function extractCustomParameterName($customKey): string
    {
        // Для ключей вида "custom_1760023038696" возвращаем "Дополнительный параметр"
        // Но в нашем случае это не будет использоваться, т.к. есть labels
        if (preg_match('/^custom_\d+$/', $customKey)) {
            return 'Дополнительный параметр';
        }

        return 'Дополнительный параметр';
    }

    /**
     * Get template for category slug
     */
    public function getTemplateForCategory($categorySlug): array
    {
        return $this->specificationTemplates[$categorySlug] ?? [];
    }

    private function getUnitForParameter($key): string
    {
        $units = [
            // Общие параметры
            'engine_power' => ' л.с.',
            'operating_weight' => ' т',
            'max_speed' => ' км/ч',
            'fuel_tank_capacity' => ' л',
            'weight' => ' т',
            'length' => ' м',
            'width' => ' м',
            'height' => ' м',

            // Экскаваторы
            'bucket_volume' => ' м³',
            'max_digging_depth' => ' м',
            'max_reach' => ' м',
            'bucket_width' => ' м',
            'arm_force' => ' кН',
            'boom_force' => ' кН',
            'digging_force' => ' кН',

            // Бульдозеры
            'blade_width' => ' м',
            'blade_height' => ' м',
            'blade_capacity' => ' м³',
            'max_cutting_depth' => ' м',
            'max_lifting_height' => ' м',

            // Самосвалы
            'load_capacity' => ' т',
            'body_volume' => ' м³',
            'body_length' => ' м',
            'body_width' => ' м',
            'body_height' => ' м',
            'unloading_angle' => '°',
            'payload' => ' т',

            // Краны
            'lifting_capacity' => ' т',
            'boom_length' => ' м',
            'outreach' => ' м',
            'rotation_angle' => '°',
            'counterweight_weight' => ' т',

            // Погрузчики
            'fork_length' => ' м',
            'lifting_height' => ' м',
            'center_of_gravity' => ' мм',
            'tilting_angle' => '°',
            'breakout_force' => ' кН',

            // Катки
            'roller_width' => ' м',
            'roller_diameter' => ' м',
            'vibration_frequency' => ' Гц',
            'amplitude' => ' мм',
            'compaction_width' => ' м',
            'compaction_force' => ' кН',

            // Бетононасосы
            'concrete_output' => ' м³/ч',
            'max_pressure' => ' бар',
            'pipe_diameter' => ' мм',
            'vertical_reach' => ' м',
            'horizontal_reach' => ' м',

            // Генераторы
            'power' => ' кВт',
            'voltage' => ' В',
            'current' => ' А',
            'frequency' => ' Гц',

            // Компрессоры
            'air_flow' => ' м³/мин',
            'working_pressure' => ' бар',
            'receiver_volume' => ' л',

            // Сварочные аппараты
            'welding_current' => ' А',
            'duty_cycle' => '%',
            'power_consumption' => ' кВт',

            // Автогрейдеры
            'blade_length' => ' м',
            'blade_height' => ' м',

            // Манипуляторы
            'max_reach' => ' м',
            'rotation_angle' => '°',

            // Дополнительные параметры
            'axle_configuration' => '',
            'transmission' => '',
            'drive_type' => '',
            'blade_type' => '',

            // English variants
            'Engine power' => ' л.с.',
            'Operating weight' => ' т',
            'Bucket volume' => ' м³',
            'Max digging depth' => ' м',
            'Blade width' => ' м',
            'Blade height' => ' м',
            'Load capacity' => ' т',
            'Body volume' => ' м³',
            'Max speed' => ' км/ч',
            'Lifting capacity' => ' т',
            'Boom length' => ' м',
            'Power' => ' кВт'
        ];

        return $units[$key] ?? '';
    }

    /**
     * Validate specifications against category template
     */
    public function validateSpecifications($categoryName, $specifications): array
    {
        $categorySlug = $this->getCategoryKey($categoryName);
        $template = $this->getTemplateForCategory($categorySlug);
        $errors = [];

        foreach ($specifications as $key => $value) {
            if (isset($template[$key])) {
                if ($template[$key]['type'] === 'number' && !is_numeric($value)) {
                    $errors[] = "Параметр '{$template[$key]['label']}' должен быть числом";
                }
            }
        }

        return $errors;
    }

    public function getTemplates(): array
    {
        return array_keys($this->specificationTemplates);
    }

    /**
     * Format specification for display
     */
    public function formatSpecification($key, $value, $categoryName): string
    {
        $categorySlug = $this->getCategoryKey($categoryName);
        $template = $this->getTemplateForCategory($categorySlug);

        if (isset($template[$key])) {
            $unit = $template[$key]['unit'] ?? '';
            return "{$template[$key]['label']}: {$value} {$unit}";
        }

        return ucfirst(str_replace('_', ' ', $key)) . ": {$value}";
    }

    public function getCategoryKey($categoryName): string  // БЫЛО: private function getCategoryKey
    {
        $map = [
            'Гусеничный экскаватор' => 'gusenicnyi-ekskavator',
            'Экскаватор-погрузчик' => 'ekskavator-pogruzcik',
            'Бульдозер' => 'buldozer',
            'Фронтальный погрузчик' => 'frontalnyi-pogruzcik',
            'Кран' => 'kran',
            'Манипулятор' => 'manipuliator',
            'Каток дорожный' => 'katok-doroznyi',
            'Автобетононасос' => 'avtobetononasos',
            'Автогрейдер' => 'avtogreider',
            'Самосвал' => 'samosval'
        ];

        return $map[$categoryName] ?? $this->slugify($categoryName);
    }

    /**
     * Get parameter label
     */
    public function getParameterLabel($key, $categoryName): string
    {
        $categorySlug = $this->getCategoryKey($categoryName);
        $template = $this->getTemplateForCategory($categorySlug);
        return $template[$key]['label'] ?? $this->formatLabel($key);
    }


    /**
     * Helper function to create slug from category name
     */
    private function slugify($text): string
    {
        // Replace russian characters
        $text = str_replace(
            ['а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
             'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'],
            ['a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya',
             'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya'],
            $text
        );

        // Replace spaces and special characters
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return $text;
    }

    private function formatLabel($key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }
}
