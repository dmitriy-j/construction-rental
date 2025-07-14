<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Models\EquipmentRentalTerm;

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
        ->selectSub($minPriceSubquery, 'min_price')
        ->with(['category', 'rentalTerms', 'images', 'company'])
        ->where('is_approved', true)
        ->has('rentalTerms');



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
            $query->whereHas('rentalTerms', function($q) use ($request) {
                $q->where('price_per_hour', '>=', $request->min_price);
            });
        }

        // Фильтр по статусу
            if ($request->status) {
                $query->where(function($q) use ($request) {
                    $today = now()->format('Y-m-d');

                    if ($request->status === 'available') {
                        $q->whereDoesntHave('availabilities', function($sub) use ($today) {
                            $sub->where('date', $today)
                                ->where(function($query) {
                                    $query->where('status', 'booked')
                                        ->orWhere('status', 'maintenance')
                                        ->orWhere(function($q) {
                                            $q->where('status', 'temp_reserve')
                                                ->where('expires_at', '>', now());
                                        });
                                });
                        });
                    }
                    elseif ($request->status === 'unavailable') {
                        $q->whereHas('availabilities', function($sub) use ($today) {
                            $sub->where('date', $today)
                                ->where(function($query) {
                                    $query->where('status', 'booked')
                                        ->orWhere('status', 'maintenance')
                                        ->orWhere(function($q) {
                                            $q->where('status', 'temp_reserve')
                                                ->where('expires_at', '>', now());
                                        });
                                });
                        });
                    }
                elseif ($request->status === 'maintenance') {
                    // Техника на обслуживании
                    $q->whereHas('availabilities', function($sub) {
                        $sub->where('date', '>=', now())
                            ->where('status', 'maintenance');
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

        $equipment->increment('views');
        return view('catalog.show', compact('equipment', 'defaultStart', 'defaultEnd'));
    }
}
