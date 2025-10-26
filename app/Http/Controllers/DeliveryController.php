<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\RentalCondition;
use App\Services\DeliveryCalculatorService;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function calculate(Request $request)
    {
        $request->validate([
            'from_id' => 'required|exists:locations,id',
            'to_id' => 'required|exists:locations,id',
            'condition_id' => 'required|exists:rental_conditions,id',
        ]);

        $from = Location::find($request->from_id);
        $to = Location::find($request->to_id);
        $condition = RentalCondition::find($request->condition_id);

        $cost = app(DeliveryCalculatorService::class)
            ->calculateDeliveryCost($from, $to, $condition);

        return response()->json(['cost' => $cost]);
    }

    public function getLocations(Request $request)
    {
        // Пример реализации (адаптируйте под ваши нужды)
        $locations = Location::query();

        if ($request->has('company_id')) {
            $locations->where('company_id', $request->company_id);
        }

        return response()->json($locations->get());
    }
}
