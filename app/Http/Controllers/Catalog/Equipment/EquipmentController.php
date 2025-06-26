<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\Location;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipments = Equipment::where('company_id', auth()->guard('company')->id())->get();
        return view('tenant.equipment.index', compact('equipments'));
    }

    public function create()
    {
        $categories = Category::all();
        $locations = Location::all();
        return view('tenant.equipment.create', compact('categories', 'locations'));
    }

    public function store(StoreEquipmentRequest $request)
    {
        $equipment = Equipment::create([
            'title' => $request->title,
            'slug' => \Illuminate\Support\Str::slug($request->title),
            'description' => $request->description,
            'company_id' => auth()->guard('company')->id(),
            'category_id' => $request->category_id,
            'location_id' => $request->location_id,
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'hours_worked' => $request->hours_worked,
            'is_approved' => false,
        ]);
            // В метод store после создания оборудования
            $equipment->rentalTerms()->create([
                'period' => 'час',
                'price' => $request->price_per_hour,
                'currency' => 'RUB'
            ]);
        foreach ($request->images as $key => $image) {
            $path = $image->store('public/equipment');
            $equipment->images()->create([
                'path' => str_replace('public/', '', $path),
                'is_main' => $key === 0
            ]);
        }

        return redirect()->route('tenant.equipment.index');
    }

    public function show(Equipment $equipment)
    {
        if ($equipment->company_id !== auth()->guard('company')->id()) {
            abort(403);
        }
        return view('tenant.equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        if ($equipment->company_id !== auth()->guard('company')->id()) {
            abort(403);
        }
        $categories = Category::all();
        $locations = Location::all();
        return view('tenant.equipment.edit', compact('equipment', 'categories', 'locations'));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
    {
        if ($equipment->company_id !== auth()->guard('company')->id()) {
            abort(403);
        }

        $equipment->update([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'location_id' => $request->location_id,
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'hours_worked' => $request->hours_worked,
            'is_approved' => false,
        ]);

        if ($request->hasFile('images')) {
            foreach ($equipment->images as $image) {
                Storage::delete('public/' . $image->path);
                $image->delete();
            }

            foreach ($request->images as $key => $image) {
                $path = $image->store('public/equipment');
                $equipment->images()->create([
                    'path' => str_replace('public/', '', $path),
                    'is_main' => $key === 0
                ]);
            }
        }

        return redirect()->route('tenant.equipment.show', $equipment);
    }

    public function destroy(Equipment $equipment)
    {
        if ($equipment->company_id !== auth()->guard('company')->id()) {
            abort(403);
        }

        foreach ($equipment->images as $image) {
            Storage::delete('public/' . $image->path);
            $image->delete();
        }

        $equipment->delete();
        return redirect()->route('tenant.equipment.index');
    }
}
