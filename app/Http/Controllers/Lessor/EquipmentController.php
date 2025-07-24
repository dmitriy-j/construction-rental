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

        \Log::info('[EquipmentController] Store request data:', $request->all());

        try {
             DB::beginTransaction();
            \Log::debug('Transaction started');

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

            \Log::info("Equipment created", ['id' => $equipment->id]);

            // Сохраняем спецификации
            $specs = $request->input('specifications');
            \Log::debug('Specifications data', $specs);

            $equipment->specifications()->createMany([
                ['key' => 'weight', 'value' => $specs['weight'], 'weight' => $specs['weight']],
                ['key' => 'length', 'value' => $specs['length'], 'length' => $specs['length']],
                ['key' => 'width', 'value' => $specs['width'], 'width' => $specs['width']],
                ['key' => 'height', 'value' => $specs['height'], 'height' => $specs['height']],
            ]);
            \Log::info("Specifications created");


            // Создаем тарифы
            \Log::debug('Creating rental terms', [
                'price_per_hour' => $request->price_per_hour
            ]);
            $this->createRentalTerms($equipment, $request);

            // Обработка изображений
            if ($request->hasFile('images')) {
                \Log::info('Processing images', ['count' => count($request->images)]);
                foreach ($request->images as $key => $image) {
                    $path = $image->store('public/equipment');
                    $equipment->images()->create([
                        'path' => str_replace('public/', '', $path),
                        'is_main' => $key === 0
                    ]);
                }
            }

            DB::commit();
            \Log::info("Equipment fully created");

            return redirect()->route('lessor.equipment.show', $equipment);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Store error: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            // Используем встроенный логгер Laravel
            logger()->error('Ошибка при создании техники: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Ошибка при сохранении: ' . $e->getMessage()]);
        }
    }

    public function show(Equipment $equipment)
    {
        $this->authorize('view', $equipment);
        $equipment->load('specifications'); // Явная загрузка
        return view('lessor.equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        $this->authorize('update', $equipment);
        $equipment->load('rentalTerms', 'specifications');

        // Получаем почасовой тариф
        $pricePerHour = $equipment->rentalTerms->first()->price_per_hour ?? '';

        return view('lessor.equipment.edit', [
            'equipment' => $equipment,
            'categories' => $this->categories,
            'locations' => $this->locations,
            'pricePerHour' => $pricePerHour // Передаем только почасовую цену
        ]);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
    {
        \Log::info('[EquipmentController] Update request data:', [
            'equipment_id' => $equipment->id,
            'request' => $request->all()
        ]);

        try {
            $this->authorize('update', $equipment);

            // Основные данные
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
            \Log::info("Equipment base data updated");

            // Спецификации - исправленный блок
            $specs = $request->input('specifications');
            \Log::debug('Specifications data', $specs);

            // Явное сохранение для каждого параметра
            $this->updateSpecification($equipment, 'weight', $specs['weight']);
            $this->updateSpecification($equipment, 'length', $specs['length']);
            $this->updateSpecification($equipment, 'width', $specs['width']);
            $this->updateSpecification($equipment, 'height', $specs['height']);

            // Тарифы
            \Log::debug('Updating rental terms', [
                'price_per_hour' => $request->price_per_hour
            ]);
            $this->updateRentalTerms($equipment, $request);

            // Удаление отмеченных изображений
            if ($request->has('delete_images')) {
                \Log::info('Deleting images', ['images' => $request->delete_images]);
                foreach ($request->delete_images as $imageId) {
                    $image = EquipmentImage::find($imageId);
                    if ($image && $image->equipment_id == $equipment->id) {
                        Storage::delete('public/' . $image->path);
                        $image->delete();
                        \Log::info("Image deleted", ['image_id' => $imageId]);
                    }
                }
            }

            // Добавление новых изображений
            if ($request->hasFile('images')) {
                \Log::info('Adding new images', ['count' => count($request->images)]);
                foreach ($request->images as $image) {
                    $path = $image->store('public/equipment');
                    $relativePath = str_replace('public/', '', $path);
                    $equipment->images()->create([
                        'path' => $relativePath,
                        'is_main' => false
                    ]);
                    \Log::info("New image added", ['path' => $relativePath]);
                }
            }

            // Обновление главного изображения
            if ($request->has('main_image')) {
                \Log::info('Setting main image', ['image_id' => $request->main_image]);
                EquipmentImage::where('equipment_id', $equipment->id)
                    ->update(['is_main' => false]);

                $newMainImage = EquipmentImage::find($request->main_image);
                if ($newMainImage && $newMainImage->equipment_id == $equipment->id) {
                    $newMainImage->update(['is_main' => true]);
                    \Log::info("Main image updated", ['image_id' => $request->main_image]);
                }
            }

            \Log::info("Equipment updated successfully");
            return redirect()->route('lessor.equipment.show', $equipment);

        } catch (\Exception $e) {
            \Log::error('Update error: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function updateSpecification(Equipment $equipment, string $key, $value)
    {
        $spec = $equipment->specifications()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                $key => $value
            ]
        );

        \Log::debug("Spec updated", [
            'key' => $key,
            'id' => $spec->id,
            'value' => $value,
            'spec_attributes' => $spec->getAttributes()
        ]);

        return $spec;
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
         \Log::debug('Creating rental term', [
            'equipment_id' => $equipment->id,
            'price_per_hour' => $request->price_per_hour
        ]);
        // Создаем только почасовой тариф
        $equipment->rentalTerms()->create([
            'price_per_hour' => $request->price_per_hour,
            'currency' => 'RUB'
        ]);
         \Log::info("Rental term created", ['term_id' => $term->id]);
    }


   protected function updateRentalTerms(Equipment $equipment, $request)
    {
        $term = $equipment->rentalTerms()->first();

        if ($term) {
            \Log::debug('Updating existing rental term', [
                'term_id' => $term->id,
                'old_price' => $term->price_per_hour,
                'new_price' => $request->price_per_hour
            ]);

            $term->update(['price_per_hour' => $request->price_per_hour]);
        } else {
            \Log::warning('No rental term found, creating new one');
            $this->createRentalTerms($equipment, $request);
        }
    }

    protected function createSpecifications(Equipment $equipment, $request)
    {
        $specs = $request->input('specifications');

        $equipment->specifications()->createMany([
            [
                'key' => 'weight',
                'value' => $specs['weight'],
                'weight' => $specs['weight']
            ],
            [
                'key' => 'length',
                'value' => $specs['length'],
                'length' => $specs['length']
            ],
            [
                'key' => 'width',
                'value' => $specs['width'],
                'width' => $specs['width']
            ],
            [
                'key' => 'height',
                'value' => $specs['height'],
                'height' => $specs['height']
            ]
        ]);
    }
}
