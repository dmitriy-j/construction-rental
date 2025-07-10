<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\Location;
use App\Models\EquipmentRentalTerm;
use App\Models\EquipmentImage;
use App\Http\Requests\Catalog\StoreEquipmentRequest;
use App\Http\Requests\Catalog\UpdateEquipmentRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // Добавлено
use Illuminate\Support\Facades\Log; // Добавлено
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EquipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->categories = Category::all();
            $this->locations = Location::all();
            return $next($request);
        });
    }

    public function index()
    {
        $equipments = Equipment::where('company_id', auth()->user()->company_id)
            ->with('category', 'location', 'images')
            ->get();

        return view('lessor.equipment.index', compact('equipments'));
    }

    public function create()
    {
        return view('lessor.equipment.create', [
            'categories' => $this->categories,
            'locations' => $this->locations
        ]);
    }

    public function store(StoreEquipmentRequest $request)
    {
        try {
            DB::beginTransaction();

            // Генерируем уникальный slug
            $slug = Str::slug($request->title);
            $counter = 1;

            while (Equipment::where('slug', $slug)->exists()) {
                $slug = Str::slug($request->title) . '-' . $counter;
                $counter++;
            }

            $equipment = Equipment::create([
                'title' => $request->title,
                'slug' => $slug,
                'description' => $request->description,
                'company_id' => auth()->user()->company_id,
                'category_id' => $request->category_id,
                'location_id' => $request->location_id,
                'brand' => $request->brand,
                'model' => $request->model,
                'year' => (int)$request->year,
                'hours_worked' => (float)$request->hours_worked,
                'is_approved' => false,
            ]);

            // Создаем тарифы
            $this->createRentalTerms($equipment, $request);

            // Обработка изображений
            if ($request->hasFile('images')) {
                foreach ($request->images as $key => $image) {
                    $path = $image->store('public/equipment');
                    $equipment->images()->create([
                        'path' => str_replace('public/', '', $path),
                        'is_main' => $key === 0
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('lessor.equipment.show', $equipment);

        } catch (\Exception $e) {
            DB::rollBack();
            // Используем встроенный логгер Laravel
            logger()->error('Ошибка при создании техники: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Ошибка при сохранении: ' . $e->getMessage()]);
        }
    }

    public function show(Equipment $equipment)
    {
        $this->authorize('view', $equipment);
        $equipment->load('rentalTerms', 'images', 'specifications');
        return view('lessor.equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        $this->authorize('update', $equipment);
        $equipment->load('rentalTerms');

        // Получаем цены для всех периодов
        $prices = [];
        foreach (['час', 'смена', 'сутки', 'месяц'] as $period) {
            $term = $equipment->rentalTerms->firstWhere('period', $period);
            $prices["price_per_{$period}"] = $term ? $term->price : '';
        }

        return view('lessor.equipment.edit', [
            'equipment' => $equipment,
            'categories' => $this->categories,
            'locations' => $this->locations,
            'prices' => $prices
        ]);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
    {
        $this->authorize('update', $equipment);

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

        // Обновляем тарифы
        $this->updateRentalTerms($equipment, $request);

        // Удаление отмеченных изображений
        if ($request->has('delete_images')) {
            foreach ($request->delete_images as $imageId) {
                $image = EquipmentImage::find($imageId);
                if ($image && $image->equipment_id == $equipment->id) {
                    Storage::delete('public/' . $image->path);
                    $image->delete();
                }
            }
        }

        // Добавление новых изображений
        if ($request->hasFile('images')) {
            foreach ($request->images as $image) {
                $path = $image->store('public/equipment');
                $equipment->images()->create([
                    'path' => str_replace('public/', '', $path),
                    'is_main' => false
                ]);
            }
        }

        // Обновление главного изображения
        if ($request->has('main_image')) {
            EquipmentImage::where('equipment_id', $equipment->id)
                ->update(['is_main' => false]);

            $newMainImage = EquipmentImage::find($request->main_image);
            if ($newMainImage && $newMainImage->equipment_id == $equipment->id) {
                $newMainImage->update(['is_main' => true]);
            }
        }

        return redirect()->route('lessor.equipment.show', $equipment);
    }

    public function destroy(Equipment $equipment)
    {
        $this->authorize('delete', $equipment);

        // Удаляем изображения
        foreach ($equipment->images as $image) {
            Storage::delete('public/' . $image->path);
            $image->delete();
        }

        $equipment->delete();
        return redirect()->route('lessor.equipment.index');
    }

    protected function createRentalTerms(Equipment $equipment, $request)
    {
        // Основной тариф за час
        $equipment->rentalTerms()->create([
            'period' => 'час',
            'price' => $request->price_per_hour,
            'currency' => 'RUB'
        ]);

        // Дополнительные тарифы
        $periods = [
            'смена' => 'price_per_shift',
            'сутки' => 'price_per_day',
            'месяц' => 'price_per_month'
        ];

        foreach ($periods as $period => $field) {
            // Более надежная проверка для числовых полей
            if ($request->has($field) && $request->$field !== '' && $request->$field !== null) {
                $equipment->rentalTerms()->create([
                    'period' => $period,
                    'price' => $request->$field,
                    'currency' => 'RUB'
                ]);
            }
        }
    }

    protected function updateRentalTerms(Equipment $equipment, $request)
    {
        // Периоды и соответствующие поля запроса
        $periods = [
            'час' => 'price_per_hour',
            'смена' => 'price_per_shift',
            'сутки' => 'price_per_day',
            'месяц' => 'price_per_month'
        ];

        foreach ($periods as $period => $field) {
            if ($request->has($field)) {
                EquipmentRentalTerm::updateOrCreate(
                    [
                        'equipment_id' => $equipment->id,
                        'period' => $period
                    ],
                    [
                        'price' => $request->$field,
                        'currency' => 'RUB'
                    ]
                );
            }
        }
    }
}
