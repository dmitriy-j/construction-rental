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
}