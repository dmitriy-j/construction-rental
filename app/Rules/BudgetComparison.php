<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class BudgetComparison implements Rule
{
    protected $budgetFrom;

    public function __construct($budgetFrom)
    {
        $this->budgetFrom = $budgetFrom;
    }

    public function passes($attribute, $value)
    {
        // Простое сравнение float без brick/math
        return (float) $value > (float) $this->budgetFrom;
    }

    public function message()
    {
        return 'Поле "Бюджет до" должно быть больше поля "Бюджет от".';
    }
}
