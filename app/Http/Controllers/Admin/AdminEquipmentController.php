<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\Location;
use App\Models\EquipmentImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminEquipmentController extends Controller
{
    public function index(Request $request)
    {
        $validSortColumns = [
            'id', 'title', 'brand', 'model', 'year', 'hours_worked', 'is_approved', 'is_platform_owned', 'created_at',
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
                'is_platform_owned',
                'created_at',
            ])
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('is_approved'), fn ($q) => $q->where('is_approved', $request->is_approved))
            ->when($request->filled('is_platform_owned'), fn ($q) => $q->where('is_platform_owned', $request->is_platform_owned))
            ->when($request->filled('owner_type'), function ($q) use ($request) {
                if ($request->owner_type === 'platform') {
                    $q->where('is_platform_owned', true);
                } elseif ($request->owner_type === 'lessor') {
                    $q->where('is_platform_owned', false);
                }
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(20);

        $companies = Company::all();
        $categories = Category::all();

        return view('admin.equipment.index', compact('equipment', 'companies', 'categories', 'sortColumn', 'sortDirection'));
    }

    public function create()
    {
        $categories = Category::all();
        $locations = Location::where('company_id', auth()->user()->company_id)->get();
        $companies = Company::all();

        return view('admin.equipment.create', compact('categories', 'locations', 'companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'brand' => 'required',
            'model' => 'required',
            'year' => 'required|integer',
            'hours_worked' => 'required|numeric',
            'category_id' => 'required|exists:equipment_categories,id',
            'location_id' => 'required|exists:locations,id',
            'price_per_hour' => 'required|numeric',
            'description' => 'nullable|string',
            'is_platform_owned' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        try {
            DB::beginTransaction();

            // Генерируем уникальный slug
            $slug = Str::slug($request->title);
            $counter = 1;
            while (Equipment::where('slug', $slug)->exists()) {
                $slug = Str::slug($request->title).'-'.$counter;
                $counter++;
            }

            // Если техника платформенная — company_id = null, is_approved = true
            $isPlatformOwned = $request->boolean('is_platform_owned');
            $companyId = $isPlatformOwned ? null : $request->company_id;

            $equipment = Equipment::create([
                'title' => $request->title,
                'slug' => $slug,
                'description' => $request->description ?? '',
                'company_id' => $companyId,
                'category_id' => $request->category_id,
                'location_id' => $request->location_id,
                'brand' => $request->brand,
                'model' => $request->model,
                'year' => (int) $request->year,
                'hours_worked' => (float) $request->hours_worked,
                'is_platform_owned' => $isPlatformOwned,
                'is_approved' => $isPlatformOwned ? true : false,
            ]);

            // Создаём тариф
            EquipmentRentalTerm::create([
                'equipment_id' => $equipment->id,
                'price_per_hour' => $request->price_per_hour,
                'currency' => 'RUB',
            ]);

            DB::commit();

            return redirect()->route('admin.equipment.show', $equipment)
                ->with('success', 'Техника создана!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Admin create equipment error: '.$e->getMessage());

            return back()->withInput()->withErrors(['error' => 'Ошибка при создании: '.$e->getMessage()]);
        }
    }

    public function approve(Equipment $equipment)
    {
        $equipment->update(['is_approved' => true]);

        return back()->with('success', 'Техника одобрена!');
    }

    public function reject(Equipment $equipment)
    {
        $equipment->update(['is_approved' => false]);

        return back()->with('success', 'Техника отклонена!');
    }

    public function show(Equipment $equipment)
    {
        $equipment->load([
            'company',
            'category',
            'location',
            'rentalTerms',
            'images',
            'specifications',
        ]);

        $companies = Company::all();
        $locations = Location::all();

        return view('admin.equipment.show', compact('equipment', 'companies', 'locations'));
    }

    public function edit(Equipment $equipment)
    {
        $categories = Category::all();
        $locations = Location::where('company_id', auth()->user()->company_id)->get();
        $companies = Company::all();

        return view('admin.equipment.edit', compact('equipment', 'categories', 'locations', 'companies'));
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'brand' => 'required',
            'model' => 'required',
            'year' => 'required|integer',
            'hours_worked' => 'required|numeric',
            'company_id' => 'nullable|exists:companies,id',
            'location_id' => 'required|exists:locations,id',
            'price_per_hour' => 'required|numeric',
            'price_per_km' => 'nullable|numeric',
            'min_rental_hours' => 'required|integer',
            'delivery_days' => 'required|integer',
            'description' => 'nullable|string',
            'is_approved' => 'boolean',
            'is_platform_owned' => 'boolean',
            'rating' => 'required|numeric|min:0|max:5',
            'views' => 'required|integer|min:0',
        ]);

        // Если техника платформенная — company_id = null
        $isPlatformOwned = $request->boolean('is_platform_owned');
        $updateData = $validated;
        $updateData['company_id'] = $isPlatformOwned ? null : $request->company_id;
        $updateData['is_platform_owned'] = $isPlatformOwned;

        $equipment->update($updateData);

        // Обновление условий аренды
        $term = $equipment->rentalTerms->first();
        if ($term) {
            $term->update([
                'price_per_hour' => $request->price_per_hour,
                'price_per_km' => $request->price_per_km ?? 0,
                'min_rental_hours' => $request->min_rental_hours,
                'delivery_days' => $request->delivery_days,
            ]);
        } else {
            EquipmentRentalTerm::create([
                'equipment_id' => $equipment->id,
                'price_per_hour' => $request->price_per_hour,
                'currency' => 'RUB',
            ]);
        }

        return redirect()->route('admin.equipment.show', $equipment)
            ->with('success', 'Изменения сохранены!');
    }

    public function destroy(Equipment $equipment)
    {
        foreach ($equipment->images as $image) {
            Storage::delete('public/'.$image->path);
            $image->delete();
        }

        $equipment->delete();

        return redirect()->route('admin.equipment.index')
            ->with('success', 'Техника удалена!');
    }
}
