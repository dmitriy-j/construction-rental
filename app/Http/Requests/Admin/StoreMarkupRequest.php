<?php

namespace App\Http\Requests\Admin;

use App\Models\PlatformMarkup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreMarkupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', PlatformMarkup::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Обработка nullable полей
        $this->merge([
            'markupable_id' => $this->markupable_id ? (int) $this->markupable_id : null,
            'value' => (float) $this->value,
            'priority' => (int) $this->priority,
            'is_active' => $this->boolean('is_active'),
            'rules' => $this->rules ?: [],
            'valid_from' => $this->valid_from ?: null,
            'valid_to' => $this->valid_to ?: null,
        ]);

        // Очистка правил если тип наценки не требует их
        if (!in_array($this->type, ['tiered', 'combined', 'seasonal'])) {
            $this->merge(['rules' => []]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $markupableTypes = [
            \App\Models\Equipment::class,
            \App\Models\EquipmentCategory::class,
            \App\Models\Company::class,
            null
        ];

        $baseRules = [
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'markupable_type' => ['nullable', Rule::in($markupableTypes)],
            'markupable_id' => ['nullable', 'required_with:markupable_type', 'integer'],
            'entity_type' => ['required', 'string', Rule::in(['order', 'rental_request', 'proposal'])],
            'type' => ['required', 'string', Rule::in(['fixed', 'percent', 'tiered', 'combined', 'seasonal'])],
            'calculation_type' => ['required', 'string', Rule::in(['addition', 'multiplication', 'complex'])],
            'value' => ['required', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
            'valid_from' => ['nullable', 'date', 'before_or_equal:valid_to'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];

        // Добавляем правила для конкретных типов наценок
        return array_merge($baseRules, $this->getTypeSpecificRules());
    }

    /**
     * Get validation rules for specific markup types
     */
    protected function getTypeSpecificRules(): array
    {
        return match($this->type) {
            'tiered' => $this->getTieredRules(),
            'combined' => $this->getCombinedRules(),
            'seasonal' => $this->getSeasonalRules(),
            default => []
        };
    }

    /**
     * Get validation rules for tiered markups
     */
    protected function getTieredRules(): array
    {
        return [
            'rules.tiers' => ['required', 'array', 'min:1', 'max:' . config('markups.tiered.max_tiers')],
            'rules.tiers.*.min' => ['required', 'integer', 'min:0'],
            'rules.tiers.*.max' => ['required', 'integer', 'min:1', 'gt:rules.tiers.*.min'],
            'rules.tiers.*.type' => ['required', 'string', Rule::in(['fixed', 'percent'])],
            'rules.tiers.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get validation rules for combined markups
     */
    protected function getCombinedRules(): array
    {
        return [
            'rules.fixed_value' => ['required', 'numeric', 'min:0', 'max:' . config('markups.limits.max_fixed_markup')],
            'rules.percent_value' => ['required', 'numeric', 'min:0', 'max:' . config('markups.limits.max_percent_markup')],
        ];
    }

    /**
     * Get validation rules for seasonal markups
     */
    protected function getSeasonalRules(): array
    {
        return [
            'rules.high_season_coefficient' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'rules.medium_season_coefficient' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'rules.low_season_coefficient' => ['required', 'numeric', 'min:0.1', 'max:10'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateEntityExists($validator);
            $this->validatePriorityRange($validator);
            $this->validateValueLimits($validator);
            $this->validateUniqueMarkup($validator);
            $this->validateTiersOrder($validator);
        });
    }

    /**
     * Validate that entity exists
     */
    protected function validateEntityExists($validator): void
    {
        if (!$this->markupable_type || !$this->markupable_id) {
            return;
        }

        $exists = match($this->markupable_type) {
            \App\Models\Equipment::class => \App\Models\Equipment::where('id', $this->markupable_id)->exists(),
            \App\Models\EquipmentCategory::class => \App\Models\EquipmentCategory::where('id', $this->markupable_id)->exists(),
            \App\Models\Company::class => \App\Models\Company::where('id', $this->markupable_id)->exists(),
            default => false
        };

        if (!$exists) {
            $validator->errors()->add(
                'markupable_id',
                "Сущность {$this->markupable_type} с ID {$this->markupable_id} не существует."
            );
        }
    }

    /**
     * Validate priority range based on markupable type
     */
    protected function validatePriorityRange($validator): void
    {
        $priority = $this->priority;
        $markupableType = $this->markupable_type;

        $ranges = config('markups.priorities');

        $expectedRange = match($markupableType) {
            \App\Models\Equipment::class => $ranges['equipment'],
            \App\Models\EquipmentCategory::class => $ranges['category'],
            \App\Models\Company::class => $ranges['company'],
            null => $ranges['general'],
            default => $ranges['general']
        };

        if ($priority < $expectedRange['min'] || $priority > $expectedRange['max']) {
            $validator->errors()->add(
                'priority',
                "Для {$this->getMarkupableTypeLabel($markupableType)} приоритет должен быть в диапазоне {$expectedRange['min']}-{$expectedRange['max']}."
            );
        }
    }

    /**
     * Validate value limits based on markup type
     */
    protected function validateValueLimits($validator): void
    {
        $value = $this->value;
        $type = $this->type;

        $limits = config('markups.limits');

        if ($type === 'percent' && $value > $limits['max_percent_markup']) {
            $validator->errors()->add(
                'value',
                "Процентная наценка не может превышать {$limits['max_percent_markup']}%."
            );
        }

        if ($type === 'fixed' && $value > $limits['max_fixed_markup']) {
            $validator->errors()->add(
                'value',
                "Фиксированная наценка не может превышать {$limits['max_fixed_markup']} ₽/час."
            );
        }
    }

    /**
     * Validate that markup is unique
     */
    protected function validateUniqueMarkup($validator): void
    {
        $exists = PlatformMarkup::where('platform_id', $this->platform_id)
            ->where('entity_type', $this->entity_type)
            ->where('markupable_type', $this->markupable_type)
            ->where('markupable_id', $this->markupable_id)
            ->exists();

        if ($exists) {
            $validator->errors()->add(
                'markupable_type',
                'Наценка с такими параметрами уже существует.'
            );
        }
    }

    /**
     * Validate tiers order for tiered markups
     */
    protected function validateTiersOrder($validator): void
    {
        if ($this->type !== 'tiered' || empty($this->rules['tiers'])) {
            return;
        }

        $tiers = $this->rules['tiers'];
        $previousMax = -1;

        foreach ($tiers as $index => $tier) {
            $min = (int) $tier['min'];
            $max = (int) $tier['max'];

            if ($min <= $previousMax) {
                $validator->errors()->add(
                    "rules.tiers.{$index}.min",
                    "Ступени должны идти последовательно без перекрытий."
                );
            }

            $previousMax = $max;
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'platform_id.required' => 'Поле платформа обязательно для заполнения.',
            'platform_id.exists' => 'Выбранная платформа не существует.',

            'markupable_type.in' => 'Недопустимый тип сущности.',
            'markupable_id.required_with' => 'ID сущности обязателен при выборе типа сущности.',

            'entity_type.required' => 'Контекст применения обязателен для заполнения.',
            'entity_type.in' => 'Недопустимый контекст применения.',

            'type.required' => 'Тип наценки обязателен для заполнения.',
            'type.in' => 'Недопустимый тип наценки.',

            'calculation_type.required' => 'Тип расчета обязателен для заполнения.',
            'calculation_type.in' => 'Недопустимый тип расчета.',

            'value.required' => 'Значение наценки обязательно для заполнения.',
            'value.numeric' => 'Значение наценки должно быть числом.',
            'value.min' => 'Значение наценки не может быть отрицательным.',

            'priority.required' => 'Приоритет обязателен для заполнения.',
            'priority.integer' => 'Приоритет должен быть целым числом.',
            'priority.min' => 'Приоритет не может быть отрицательным.',
            'priority.max' => 'Приоритет не может превышать 999.',

            'valid_from.date' => 'Дата начала должна быть валидной датой.',
            'valid_to.date' => 'Дата окончания должна быть валидной датой.',
            'valid_to.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',

            'rules.tiers.required' => 'Для ступенчатой наценки необходимо указать хотя бы одну ступень.',
            'rules.tiers.max' => 'Максимальное количество ступеней: ' . config('markups.tiered.max_tiers'),

            'rules.fixed_value.required' => 'Фиксированная часть обязательна для комбинированной наценки.',
            'rules.percent_value.required' => 'Процентная часть обязательна для комбинированной наценки.',

            'rules.high_season_coefficient.required' => 'Коэффициент высокого сезона обязателен.',
            'rules.medium_season_coefficient.required' => 'Коэффициент среднего сезона обязателен.',
            'rules.low_season_coefficient.required' => 'Коэффициент низкого сезона обязателен.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'platform_id' => 'платформа',
            'markupable_type' => 'тип сущности',
            'markupable_id' => 'ID сущности',
            'entity_type' => 'контекст применения',
            'type' => 'тип наценки',
            'calculation_type' => 'тип расчета',
            'value' => 'значение наценки',
            'priority' => 'приоритет',
            'is_active' => 'статус активности',
            'valid_from' => 'действует с',
            'valid_to' => 'действует до',
            'rules.tiers' => 'ступени',
            'rules.tiers.*.min' => 'минимальное значение',
            'rules.tiers.*.max' => 'максимальное значение',
            'rules.tiers.*.type' => 'тип значения',
            'rules.tiers.*.value' => 'значение',
            'rules.fixed_value' => 'фиксированная часть',
            'rules.percent_value' => 'процентная часть',
            'rules.high_season_coefficient' => 'коэффициент высокого сезона',
            'rules.medium_season_coefficient' => 'коэффициент среднего сезона',
            'rules.low_season_coefficient' => 'коэффициент низкого сезона',
        ];
    }

    /**
     * Get markupable type label for error messages
     */
    protected function getMarkupableTypeLabel(?string $type): string
    {
        return match($type) {
            \App\Models\Equipment::class => 'оборудования',
            \App\Models\EquipmentCategory::class => 'категории',
            \App\Models\Company::class => 'компании',
            null => 'общей наценки',
            default => 'неизвестного типа'
        };
    }
}
