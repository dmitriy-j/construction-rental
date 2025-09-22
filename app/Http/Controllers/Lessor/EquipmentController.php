<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreEquipmentRequest;
use App\Http\Requests\Catalog\UpdateEquipmentRequest;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentImage;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EquipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->categories = Category::all();

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
        $equipment = new Equipment;

        return view('lessor.equipment.create', [
            'categories' => $this->categories,
            'equipment' => $equipment,
        ]);
    }

    public function store(StoreEquipmentRequest $request)
    {
        \Log::info('[EquipmentController] Store request data:', $request->all());

        try {
            DB::beginTransaction();

            // Создаем новую локацию
            $location = Location::create([
                'name' => $request->location_name,
                'address' => $request->location_address,
                'company_id' => auth()->user()->company_id,
                'latitude' => 0.0,
                'longitude' => 0.0,
            ]);

            // Автоматическое геокодирование
            $this->geocodeLocation($location);

            // Генерируем уникальный slug
            $slug = Str::slug($request->title);
            $counter = 1;

            while (Equipment::where('slug', $slug)->exists()) {
                $slug = Str::slug($request->title).'-'.$counter;
                $counter++;
            }

            $equipment = Equipment::create([
                'title' => $request->title,
                'slug' => $slug,
                'description' => $request->description,
                'company_id' => auth()->user()->company_id,
                'category_id' => $request->category_id,
                'location_id' => $location->id,
                'brand' => $request->brand,
                'model' => $request->model,
                'year' => (int) $request->year,
                'hours_worked' => (float) $request->hours_worked,
                'is_approved' => false,
            ]);

            // Сохраняем спецификации
            $specs = $request->input('specifications');
            $equipment->specifications()->createMany([
                ['key' => 'weight', 'value' => $specs['weight'], 'weight' => $specs['weight']],
                ['key' => 'length', 'value' => $specs['length'], 'length' => $specs['length']],
                ['key' => 'width', 'value' => $specs['width'], 'width' => $specs['width']],
                ['key' => 'height', 'value' => $specs['height'], 'height' => $specs['height']],
            ]);

            // Создаем тарифы
            $this->createRentalTerms($equipment, $request);

            // Обработка изображений
            if ($request->hasFile('images')) {
                foreach ($request->images as $key => $image) {
                    $path = $image->store('public/equipment');
                    $equipment->images()->create([
                        'path' => str_replace('public/', '', $path),
                        'is_main' => $key === 0,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('lessor.equipment.show', $equipment);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Store error: '.$e->getMessage());

            return back()->withInput()->withErrors(['error' => 'Ошибка при сохранении: '.$e->getMessage()]);
        }
    }

    public function show(Equipment $equipment)
    {
        $this->authorize('view', $equipment);
        $equipment->load('specifications');

        return view('lessor.equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        $this->authorize('update', $equipment);

        $categories = Category::all();
        $locations = Location::all();

        // Получаем цену из первого тарифа оборудования
        $pricePerHour = optional($equipment->rentalTerms->first())->price_per_hour;

        return view('lessor.equipment.edit', compact(
            'equipment',
            'categories',
            'locations',
            'pricePerHour' // Передаем переменную в шаблон
        ));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment)
    {
        try {
            $this->authorize('update', $equipment);

            // Создаем новую локацию или обновляем существующую
            $locationData = [
                'name' => $request->location_name,
                'address' => $request->location_address,
                'company_id' => auth()->user()->company_id,
            ];

            if ($equipment->location) {
                $equipment->location->update($locationData);
                $location = $equipment->location;
                $locationId = $location->id;

                // Обновляем координаты при изменении адреса
                $this->geocodeLocation($location);
            } else {
                $location = Location::create([
                    'name' => $request->location_name,
                    'address' => $request->location_address,
                    'company_id' => auth()->user()->company_id,
                    'latitude' => 0.0,
                    'longitude' => 0.0,
                ]);
                $locationId = $location->id;

                // Геокодируем новую локацию
                $this->geocodeLocation($location);
            }

            // Основные данные
            $equipment->update([
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'location_id' => $locationId,
                'brand' => $request->brand,
                'model' => $request->model,
                'year' => $request->year,
                'hours_worked' => $request->hours_worked,
                'is_approved' => false,
            ]);

            // Спецификации
            $specs = $request->input('specifications');
            $this->updateSpecification($equipment, 'weight', $specs['weight']);
            $this->updateSpecification($equipment, 'length', $specs['length']);
            $this->updateSpecification($equipment, 'width', $specs['width']);
            $this->updateSpecification($equipment, 'height', $specs['height']);

            // Тарифы
            $this->updateRentalTerms($equipment, $request);

            // Удаление отмеченных изображений
            if ($request->has('delete_images')) {
                foreach ($request->delete_images as $imageId) {
                    $image = EquipmentImage::find($imageId);
                    if ($image && $image->equipment_id == $equipment->id) {
                        Storage::delete('public/'.$image->path);
                        $image->delete();
                    }
                }
            }

            // Добавление новых изображений
            if ($request->hasFile('images')) {
                foreach ($request->images as $image) {
                    $path = $image->store('public/equipment');
                    $relativePath = str_replace('public/', '', $path);
                    $equipment->images()->create([
                        'path' => $relativePath,
                        'is_main' => false,
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

        } catch (\Exception $e) {
            \Log::error('Update error: '.$e->getMessage());

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function geocodeLocation(Location $location)
    {
        try {
            $apiKey = env('YANDEX_MAPS_API_KEY');
            $address = urlencode($location->address);

            $response = Http::get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => $apiKey,
                'geocode' => $address,
                'format' => 'json',
                'results' => 1,
            ]);

            $data = $response->json();

            if (isset($data['response']['GeoObjectCollection']['featureMember'][0])) {
                $coordinates = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];

                [$longitude, $latitude] = explode(' ', $coordinates);

                $location->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                \Log::info('Location geocoded', [
                    'address' => $location->address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
            } else {
                \Log::warning("Geocoding failed for address: {$location->address}");
            }
        } catch (\Exception $e) {
            \Log::error('Geocoding error: '.$e->getMessage());
        }
    }

    protected function updateSpecification(Equipment $equipment, string $key, $value)
    {
        $equipment->specifications()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, $key => $value]
        );
    }

    public function destroy(Equipment $equipment)
    {
        $this->authorize('delete', $equipment);

        foreach ($equipment->images as $image) {
            Storage::delete('public/'.$image->path);
            $image->delete();
        }

        $equipment->delete();

        return redirect()->route('lessor.equipment.index');
    }

    protected function createRentalTerms(Equipment $equipment, $request)
    {
        $equipment->rentalTerms()->create([
            'price_per_hour' => $request->price_per_hour,
            'currency' => 'RUB',
        ]);
    }

    protected function updateRentalTerms(Equipment $equipment, $request)
    {
        $term = $equipment->rentalTerms()->first();

        if ($term) {
            $term->update(['price_per_hour' => $request->price_per_hour]);
        } else {
            $this->createRentalTerms($equipment, $request);
        }
    }
}
