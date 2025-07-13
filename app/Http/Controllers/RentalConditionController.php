<?php

namespace App\Http\Controllers;

use App\Models\RentalCondition;
use App\Models\Company;
use Illuminate\Http\Request;

class RentalConditionController extends Controller
{
    public function index(Company $company)
    {
        return view('rental-conditions.index', [
            'company' => $company,
            'conditions' => $company->rentalConditions
        ]);
    }

    public function create(Company $company)
    {
        return view('rental-conditions.create', compact('company'));
    }

    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'shift_hours' => 'required|integer',
            'transportation' => 'required|in:lessor,lessee,shared',
            // ... другие правила валидации
        ]);

        $company->rentalConditions()->create(array_merge(
            $validated,
            ['is_default' => $request->has('is_default')]
        ));

        return redirect()->route('companies.conditions.index', $company);
    }

    public function setDefault(Company $company, RentalCondition $condition)
    {
        $company->rentalConditions()->update(['is_default' => false]);
        $condition->update(['is_default' => true]);

        return back()->with('success', 'Условия по умолчанию обновлены');
    }
}
