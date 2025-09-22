<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\Location;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        // Обновленный подзапрос для минимальной цены
        $minPriceSubquery = EquipmentRentalTerm::selectRaw('MIN(price_per_hour)')
            ->whereColumn('equipment_id', 'equipment.id')
            ->getQuery();

        $query = Equipment::query()
            ->select('equipment.*')
            ->with(['category', 'rentalTerms', 'images', 'company']) // Упрощаем
            ->where('is_approved', true)
            ->has('rentalTerms'); // Проверяем наличие тарифов

        // Фильтр по категории
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        // Фильтр по местоположению
        if ($request->location) {
            $query->where('location_id', $request->location);
        }

        // Фильтр по цене (обновленное поле)
        if ($request->min_price) {
            $query->whereHas('rentalTerms', function ($q) use ($request) {
                $q->where('price_per_hour', '>=', $request->min_price);
            });
        }

        // Фильтр по статусу (обновленная версия)
        if ($request->status) {
            $query->where(function ($q) use ($request) {
                $today = now()->format('Y-m-d');

                if ($request->status === 'available') {
                    $q->whereDoesntHave('availabilities', function ($sub) use ($today) {
                        $sub->where('date', $today)
                            ->whereIn('status', [
                                EquipmentAvailability::STATUS_BOOKED,
                                EquipmentAvailability::STATUS_MAINTENANCE,
                            ]);
                    });
                } elseif ($request->status === 'unavailable') {
                    $q->whereHas('availabilities', function ($sub) use ($today) {
                        $sub->where('date', $today)
                            ->whereIn('status', [
                                EquipmentAvailability::STATUS_BOOKED,
                                EquipmentAvailability::STATUS_MAINTENANCE,
                            ]);
                    });
                } elseif ($request->status === 'maintenance') {
                    $q->whereHas('availabilities', function ($sub) use ($today) {
                        $sub->where('date', $today)
                            ->where('status', EquipmentAvailability::STATUS_MAINTENANCE);
                    });
                }
            });
        }

        // Сортировка
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('min_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('min_price', 'desc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->latest();
        }

        $equipments = $query->paginate(12);
        $categories = Category::all();
        $locations = Location::all();

        return view('catalog.index', compact('equipments', 'categories', 'locations'));
    }

    public function show(Equipment $equipment)
    {
        if ($equipment->rentalTerms->isEmpty()) {
            abort(404, 'Условия аренды не найдены');
        }

        $nextAvailable = $equipment->next_available_date;
        $defaultStart = $nextAvailable ?: now()->addDay();
        $defaultEnd = $defaultStart->copy()->addDays(1);

        // Загружаем компанию арендодателя с локациями

        $equipment->load('company.locations');

        $equipment->increment('views');

        return view('catalog.show', compact('equipment', 'defaultStart', 'defaultEnd'));
    }
}
