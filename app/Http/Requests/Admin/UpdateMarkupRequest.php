<?php

namespace App\Http\Requests\Admin;

use App\Models\PlatformMarkup;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMarkupRequest extends StoreMarkupRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $markup = $this->route('markup');

        return $this->user()->can('update', $markup);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Убираем проверку уникальности для текущей записи
        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $this->validateUpdateRestrictions($validator);
        });
    }

    /**
     * Validate update restrictions
     */
    protected function validateUpdateRestrictions($validator): void
    {
        $markup = $this->route('markup');

        // Проверка на возможность изменения истекшей наценки
        if ($markup->valid_to && $markup->valid_to->isPast()) {
            $validator->errors()->add(
                'valid_to',
                'Нельзя изменять наценки с истекшим сроком действия.'
            );
        }

        // Проверка на изменение типа при наличии зависимостей
        if ($this->type !== $markup->type && $this->hasCalculationHistory($markup)) {
            $validator->errors()->add(
                'type',
                'Нельзя изменять тип наценки, которая уже использовалась в расчетах.'
            );
        }
    }

    /**
     * Check if markup has calculation history
     */
    protected function hasCalculationHistory(PlatformMarkup $markup): bool
    {
        // Здесь должна быть проверка на наличие расчетов с этой наценкой
        // Временно возвращаем false
        return false;
    }

    /**
     * Validate that markup is unique (excluding current)
     */
    protected function validateUniqueMarkup($validator): void
    {
        $markup = $this->route('markup');

        $exists = PlatformMarkup::where('platform_id', $this->platform_id)
            ->where('entity_type', $this->entity_type)
            ->where('markupable_type', $this->markupable_type)
            ->where('markupable_id', $this->markupable_id)
            ->where('id', '!=', $markup->id)
            ->exists();

        if ($exists) {
            $validator->errors()->add(
                'markupable_type',
                'Наценка с такими параметрами уже существует.'
            );
        }
    }
}
