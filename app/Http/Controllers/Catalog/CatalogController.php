<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::query()
            ->with(['category', 'rentalTerms', 'images', 'company'])
            ->where('is_approved', true)
            ->has('rentalTerms'); // Фильтр техники с условиями аренды

        // Фильтр по категории
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        // Фильтр по местоположению
        if ($request->location) {
            $query->where('location_id', $request->location);
        }

        // Фильтр по цене
        if ($request->min_price) {
            $query->whereHas('rentalTerms', function($q) use ($request) {
                $q->where('price', '>=', $request->min_price);
            });
        }

        // Поиск по характеристикам
        if ($request->has('weight')) {
            $query->whereHas('specifications', function($q) use ($request) {
                $q->where('key', 'weight')
                    ->where('value', '>=', $request->weight);
            });
        }

        // Сортировка
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_per_hour', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_per_hour', 'desc');
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
        // Проверка наличия условий аренды
        if ($equipment->rentalTerms->isEmpty()) {
            abort(404, 'Условия аренды не найдены');
        }

        $equipment->increment('views');
        return view('catalog.show', compact('equipment'));
    }
}
