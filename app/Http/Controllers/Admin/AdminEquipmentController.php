<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Company;
use App\Models\Category;
use App\Models\Location; 
use Illuminate\Http\Request;

class AdminEquipmentController extends Controller
{
    public function index(Request $request)
    {
        $equipment = Equipment::query() // Исправлено Equipment:query() на Equipment::query()
            ->with(['company', 'category', 'location','rentalTerms'])
            ->when($request->filled('search'), fn($q) => $q->where('title', 'like', "%{$request->search}%")) // Фикс кавычек
            ->when($request->filled('company_id'), fn($q) => $q->where('company_id', $request->company_id)) // Фикс кавычек
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id)) // Фикс кавычек
            ->when($request->filled('is_approved'), fn($q) => $q->where('is_approved', $request->is_approved)) // Фикс кавычек
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $companies = Company::all(); // Исправлено Company:all() на Company::all()
        $categories = Category::all(); // Исправлено EquipmentCategory:all()
        

        $validSortColumns = [
        'id', 'title', 'brand', 'model', 'year', 'hours_worked', 'is_approved', 'created_at'
    ];
    
    $sortColumn = in_array($request->sort, $validSortColumns) ? $request->sort : 'created_at';
    $sortDirection = $request->direction === 'desc' ? 'desc' : 'asc';

    $equipment = Equipment::query()
        ->with(['company', 'category', 'location', 'rentalTerms'])
        ->select([
            'id',
            'title',
            'company_id',
            'category_id',
            'location_id',
            'brand',
            'model',
            'year',
            'hours_worked',
            'is_approved',
            'created_at'
        ])
        ->when($request->filled('search'), fn($q) => $q->where('title', 'like', "%{$request->search}%"))
        ->when($request->filled('company_id'), fn($q) => $q->where('company_id', $request->company_id))
        ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
        ->when($request->filled('is_approved'), fn($q) => $q->where('is_approved', $request->is_approved))
        ->orderBy($sortColumn, $sortDirection)
        ->paginate(20);

    $companies = Company::all();
    $categories = Category::all();

    return view('admin.equipment.index', compact('equipment', 'companies', 'categories', 'sortColumn', 'sortDirection'));
    }

    public function approve(Equipment $equipment)
    {
        $equipment->update(['is_approved' => true]);
        return back()->with('success', 'Техника одобрена!'); // Добавлена закрывающая кавычка
    }

    public function reject(Equipment $equipment)
    {
        $equipment->update(['is_approved' => false]);
        return back()->with('success', 'Техника отклонена!'); // Добавлена закрывающая кавычка
    }
    public function show($id)
{
    $equipment = Equipment::with([
        'company', 
        'category', 
        'location', 
        'rentalTerms',
        'images',
        'specifications'
    ])->findOrFail($id);

    $companies = Company::all(); // Получаем все компании
    $locations = Location::all(); // Получаем все локации

    return view('admin.equipment.show', compact('equipment', 'companies', 'locations'));
}

public function update(Request $request, Equipment $equipment)
{
    $validated = $request->validate([
        'title' => 'required|max:255',
        'brand' => 'required',
        'model' => 'required',
        'year' => 'required|integer',
        'hours_worked' => 'required|numeric',
        'company_id' => 'required|exists:companies,id',
        'location_id' => 'required|exists:locations,id',
        'price_per_hour' => 'required|numeric',
        'price_per_km' => 'nullable|numeric',
        'min_rental_hours' => 'required|integer',
        'delivery_days' => 'required|integer',
        'description' => 'nullable|string',
        'is_approved' => 'boolean',
        'rating' => 'required|numeric|min:0|max:5',
        'views' => 'required|integer|min:0'
    ]);

    $equipment->update($validated);

    // Обновление условий аренды
    if ($term = $equipment->rentalTerms->first()) {
        $term->update([
            'price_per_hour' => $request->price_per_hour,
            'price_per_km' => $request->price_per_km,
            'min_rental_hours' => $request->min_rental_hours,
            'delivery_days' => $request->delivery_days
        ]);
    }

    

    // Обработка изображений (если нужно)
    // ...

    return redirect()->route('admin.equipment.show', $equipment)
        ->with('success', 'Изменения сохранены!');
}
}