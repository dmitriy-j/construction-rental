<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RentalCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalConditionController extends Controller
{
    public function index(Company $company)
    {
        return view('rental-conditions.index', [
            'company' => $company,
            'conditions' => $company->rentalConditions()
                ->with(['contract', 'deliveryLocation'])
                ->get(),
        ]);
    }

    public function create(Company $company)
    {
        $activeContracts = $company->contracts()
            ->where('is_active', true)
            ->get();

        $locations = $company->locations;

        return view('rental-conditions.create', compact(
            'company',
            'activeContracts',
            'locations'
        ));
    }

    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'shift_hours' => 'required|integer|min:1|max:24',
            'shifts_per_day' => 'required|integer|min:1|max:3',
            'transportation' => 'required|in:lessor,lessee,shared',
            'fuel_responsibility' => 'required|in:lessor,lessee',
            'extension_policy' => 'required|in:allowed,not_allowed,conditional',
            'payment_type' => 'required|in:hourly,shift,daily,mileage,volume',
            'delivery_location_id' => 'required|exists:locations,id',
            'is_default' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($company, $validated) {
            // Сброс предыдущего условия по умолчанию
            if ($validated['is_default'] ?? false) {
                $company->rentalConditions()->update(['is_default' => false]);
            }

            $company->rentalConditions()->create($validated);
        });

        return redirect()->route('companies.conditions.index', $company)
            ->with('success', 'Условия аренды успешно созданы');
    }

    public function edit(Company $company, RentalCondition $condition)
    {
        $activeContracts = $company->contracts()
            ->where('is_active', true)
            ->get();

        $locations = $company->locations;

        return view('rental-conditions.edit', compact(
            'company',
            'condition',
            'activeContracts',
            'locations'
        ));
    }

    public function update(Request $request, Company $company, RentalCondition $condition)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'shift_hours' => 'required|integer|min:1|max:24',
            'shifts_per_day' => 'required|integer|min:1|max:3',
            'transportation' => 'required|in:lessor,lessee,shared',
            'fuel_responsibility' => 'required|in:lessor,lessee',
            'extension_policy' => 'required|in:allowed,not_allowed,conditional',
            'payment_type' => 'required|in:hourly,shift,daily,mileage,volume',
            'delivery_location_id' => 'required|exists:locations,id',
            'is_default' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($condition, $validated) {
            // Сброс предыдущего условия по умолчанию
            if ($validated['is_default'] ?? false) {
                $condition->company->rentalConditions()
                    ->where('id', '!=', $condition->id)
                    ->update(['is_default' => false]);
            }

            $condition->update($validated);
        });

        return redirect()->route('companies.conditions.index', $company)
            ->with('success', 'Условия аренды успешно обновлены');
    }

    public function setDefault(Company $company, RentalCondition $condition)
    {
        DB::transaction(function () use ($company, $condition) {
            $company->rentalConditions()->update(['is_default' => false]);
            $condition->update(['is_default' => true]);
        });

        return back()->with('success', 'Условия по умолчанию обновлены');
    }
}
