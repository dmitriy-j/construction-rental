<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\Location;
use App\Services\DeliveryCalculatorService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class CatalogApiController extends Controller
{
    protected $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Список техники с фильтрацией, пагинацией и финальной ценой
     */
    public function index(Request $request)
    {
        $query = Equipment::query()
            ->with(['category', 'rentalTerms', 'images', 'location'])
            ->where('is_approved', true)
            ->has('rentalTerms');

        // Фильтр по категории
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        // Фильтр по локации
        if ($request->location) {
            $query->where('location_id', $request->location);
        }

        // Фильтр по цене
        if ($request->min_price) {
            $query->whereHas('rentalTerms', function ($q) use ($request) {
                $q->where('price_per_hour', '>=', $request->min_price);
            });
        }
        if ($request->max_price) {
            $query->whereHas('rentalTerms', function ($q) use ($request) {
                $q->where('price_per_hour', '<=', $request->max_price);
            });
        }

        // Фильтр по году
        if ($request->year_from) {
            $query->where('year', '>=', $request->year_from);
        }
        if ($request->year_to) {
            $query->where('year', '<=', $request->year_to);
        }

        // Поиск
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Автодополнение — возвращаем только названия
        if ($request->autocomplete) {
            $results = $query->select('id', 'title', 'slug', 'brand', 'model')
                ->limit(10)
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'label' => "{$e->brand} {$e->model} — {$e->title}",
                    'url' => route('catalog.show', $e),
                ]);
            return response()->json($results);
        }

        // Сортировка
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'price_asc':  $query->orderBy('year', 'asc'); break;
            case 'price_desc': $query->orderBy('year', 'desc'); break;
            case 'popular':    $query->orderBy('views', 'desc'); break;
            default:           $query->latest();
        }

        $perPage = min((int)($request->per_page ?? 12), 48);
        $equipments = $query->paginate($perPage);

        // Добавляем финальную цену для каждого элемента
        $equipments->getCollection()->transform(function ($eq) {
            $term = $eq->rentalTerms->first();
            $basePrice = $term ? (float)$term->price_per_hour : 0;
            $finalPrice = $basePrice;

            // Для внешней техники добавляем наценку
            if (!$eq->isPlatformOwned() && auth()->check() && auth()->user()->company) {
                $markup = $this->pricingService->getPlatformMarkup($eq, auth()->user()->company, 1);
                $finalPrice = $basePrice + $this->pricingService->applyMarkup($basePrice, $markup);
            }

            $eq->final_price = round($finalPrice, 2);
            $eq->base_price = $basePrice;
            $eq->main_image_url = $eq->mainImage?->path ? asset('storage/' . $eq->mainImage->path) : null;
            $eq->category_name = $eq->category?->name;
            $eq->location_name = $eq->location?->name;
            $eq->dimensions = $eq->getDimensionsAttribute();
            $eq->owner_name = $eq->owner_name;
            return $eq;
        });

        return response()->json([
            'data' => $equipments->items(),
            'meta' => [
                'current_page' => $equipments->currentPage(),
                'last_page' => $equipments->lastPage(),
                'per_page' => $equipments->perPage(),
                'total' => $equipments->total(),
            ],
            'filters' => [
                'categories' => Category::all(['id', 'name']),
                'locations' => Location::all(['id', 'name']),
            ],
        ]);
    }

    /**
     * Детальная информация с финальной ценой
     */
    public function show(Equipment $equipment)
    {
        if ($equipment->rentalTerms->isEmpty()) {
            return response()->json(['error' => 'Условия аренды не найдены'], 404);
        }

        $equipment->load(['category', 'rentalTerms', 'images', 'location', 'company', 'specifications']);
        $equipment->increment('views');

        $term = $equipment->rentalTerms->first();
        $basePrice = (float)$term->price_per_hour;
        $finalPrice = $basePrice;

        if (!$equipment->isPlatformOwned() && auth()->check() && auth()->user()->company) {
            $markup = $this->pricingService->getPlatformMarkup($equipment, auth()->user()->company, 1);
            $finalPrice = $basePrice + $this->pricingService->applyMarkup($basePrice, $markup);
        }

        $nextAvailable = $equipment->next_available_date;
        $defaultStart = $nextAvailable ?: now()->addDay();

        return response()->json([
            'id' => $equipment->id,
            'title' => $equipment->title,
            'brand' => $equipment->brand,
            'model' => $equipment->model,
            'year' => $equipment->year,
            'description' => $equipment->description,
            'hours_worked' => $equipment->hours_worked,
            'rating' => $equipment->rating,
            'is_platform_owned' => $equipment->isPlatformOwned(),
            'owner_name' => $equipment->owner_name,
            'final_price' => round($finalPrice, 2),
            'base_price' => $basePrice,
            'dimensions' => $equipment->dimensions,
            'category' => $equipment->category?->name,
            'location' => $equipment->location?->name,
            'default_start' => $defaultStart->format('Y-m-d'),
            'images' => $equipment->images?->map(fn($img) => asset('storage/' . $img->path)) ?? [],
            'specifications' => $equipment->specifications?->map(fn($s) => ['key' => $s->key, 'value' => $s->value]) ?? [],
            'availability' => $equipment->future_availability ?? [],
        ]);
    }

    /**
     * ПЕРЕРАСЧЁТ ЦЕНЫ с учётом условий аренды
     * GET /api/equipment/{id}/price
     */
    public function price(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'shifts_per_day' => 'nullable|integer|min:1|max:2',
            'hours_per_shift' => 'nullable|integer|min:1|max:24',
            'quantity' => 'nullable|integer|min:1',
            'delivery_address' => 'nullable|string|max:500',
        ]);

        if ($equipment->rentalTerms->isEmpty()) {
            return response()->json(['error' => 'Нет тарифов'], 400);
        }

        $term = $equipment->rentalTerms->first();
        $basePricePerHour = (float)$term->price_per_hour;

        $shiftsPerDay = $validated['shifts_per_day'] ?? 1;
        $hoursPerShift = $validated['hours_per_shift'] ?? 8;
        $quantity = $validated['quantity'] ?? 1;

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        $days = $start->diffInDays($end) + 1;
        $totalHours = $days * $hoursPerShift * $shiftsPerDay;

        // Наценка платформы
        $platformFeePerHour = 0;
        if (!$equipment->isPlatformOwned() && auth()->check() && auth()->user()->company) {
            $markup = $this->pricingService->getPlatformMarkup($equipment, auth()->user()->company, 1);
            $platformFeePerHour = $this->pricingService->applyMarkup($basePricePerHour, $markup);
        }

        $finalPricePerHour = $basePricePerHour + $platformFeePerHour;
        $totalBase = $basePricePerHour * $totalHours * $quantity;
        $totalFee = $platformFeePerHour * $totalHours * $quantity;
        $totalFinal = $finalPricePerHour * $totalHours * $quantity;

        // Проверка доступности
        $availabilityService = app(\App\Services\EquipmentAvailabilityService::class);
        $isAvailable = $availabilityService->isAvailable($equipment, $start, $end);

        // Расчёт доставки
        $deliveryCost = 0;
        $distanceKm = 0;
        if (!empty($validated['delivery_address']) && $equipment->location) {
            try {
                $deliveryService = app(DeliveryCalculatorService::class);
                $distanceKm = $deliveryService->calculateDistance(
                    $equipment->location,
                    new \App\Models\Location(['address' => $validated['delivery_address']])
                );
                // Стоимость: distance_km * ставка (из rentalTerms)
                $ratePerKm = (float)($term->delivery_cost_per_km ?? config('services.yandex_maps.default_rate', 50));
                $deliveryCost = $distanceKm * $ratePerKm;
            } catch (\Exception $e) {
                \Log::warning('Delivery calculation error: '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'equipment_id' => $equipment->id,
            'base_price_per_hour' => $basePricePerHour,
            'platform_fee_per_hour' => $platformFeePerHour,
            'final_price_per_hour' => $finalPricePerHour,
            'days' => $days,
            'total_hours' => $totalHours,
            'total_base' => $totalBase,
            'total_platform_fee' => $totalFee,
            'total_final' => $totalFinal,
            'total_with_delivery' => $totalFinal + $deliveryCost,
            'delivery_cost' => round($deliveryCost, 2),
            'distance_km' => round($distanceKm, 2),
            'is_platform_owned' => $equipment->isPlatformOwned(),
            'is_available' => $isAvailable,
            'owner_name' => $equipment->owner_name,
        ]);
    }

    /**
     * ПОЛУЧЕНИЕ ЗАНЯТЫХ ДАТ
     * GET /api/equipment/{id}/availability?month=7&year=2026
     */
    public function availability(Request $request, Equipment $equipment)
    {
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);

        $startOfMonth = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endOfMonth = now()->setYear($year)->setMonth($month)->endOfMonth();

        $bookedDates = \App\Models\EquipmentAvailability::where('equipment_id', $equipment->id)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->where(function ($q) {
                $q->where('status', 'booked')
                  ->orWhere('status', 'maintenance')
                  ->orWhere(function ($sq) {
                      $sq->where('status', 'temp_reserve')
                         ->where('expires_at', '>', now());
                  });
            })
            ->get()
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'));

        return response()->json([
            'success' => true,
            'equipment_id' => $equipment->id,
            'month' => $month,
            'year' => $year,
            'booked_dates' => $bookedDates,
        ]);
    }
}
