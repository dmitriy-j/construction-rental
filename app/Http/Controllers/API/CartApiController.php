<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\Location;
use App\Services\DeliveryCalculatorService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartApiController extends Controller
{
    protected $pricingService;
    protected $availabilityService;

    public function __construct(PricingService $pricingService, EquipmentAvailabilityService $availabilityService)
    {
        $this->pricingService = $pricingService;
        $this->availabilityService = $availabilityService;
    }

    public function index()
    {
        $cart = $this->getCart();
        $items = $cart->items()
            ->with(['equipment.rentalTerms', 'equipment.images', 'equipment.company', 'equipment.location'])
            ->get()
            ->map(function ($item) {
                $item->total_price = (float)$item->total_price;
                $item->base_price = (float)$item->base_price;
                $item->platform_fee = (float)$item->platform_fee;
                if ($item->equipment) {
                    $item->equipment->location_name = $item->equipment->location?->name ?? null;
                }
                return $item;
            });

        return response()->json([
            'items' => $items,
            'total' => (float)$cart->total_base_amount,
            'count' => $items->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'shifts_per_day' => 'nullable|integer|min:1|max:2',
            'hours_per_shift' => 'nullable|integer|min:1|max:24',
            'quantity' => 'nullable|integer|min:1',
            'address' => 'nullable|string|max:500',
        ]);

        $equipment = Equipment::with(['rentalTerms', 'location', 'category'])->findOrFail($validated['equipment_id']);
        $term = $equipment->rentalTerms->first();
        if (!$term) {
            return response()->json(['error' => 'Нет тарифов'], 400);
        }

        if (!$this->availabilityService->isAvailable($equipment, $validated['start_date'], $validated['end_date'])) {
            return response()->json(['error' => 'Техника недоступна на выбранные даты'], 409);
        }

        $shiftsPerDay = $validated['shifts_per_day'] ?? 1;
        $hoursPerShift = $validated['hours_per_shift'] ?? 8;
        $quantity = $validated['quantity'] ?? 1;

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        $days = $start->diffInDays($end) + 1;
        $totalHours = $days * $hoursPerShift * $shiftsPerDay;

        $basePricePerHour = (float)$term->price_per_hour;
        $finalPricePerHour = $basePricePerHour;
        $platformFeePerHour = 0;

        if (!$equipment->isPlatformOwned() && auth()->check() && auth()->user()->company) {
            $markup = $this->pricingService->getPlatformMarkup($equipment, auth()->user()->company, 1);
            $platformFeePerHour = $this->pricingService->applyMarkup($basePricePerHour, $markup);
            $finalPricePerHour = $basePricePerHour + $platformFeePerHour;
        }

        $totalPrice = $finalPricePerHour * $totalHours * $quantity;

        $deliveryCost = 0;
        $distanceKm = 0;
        if (!empty($validated['address']) && $equipment->location) {
            try {
                $locationFrom = $equipment->location;
                $weightKg = $equipment->getNumericSpecValue('weight');
                $weightTons = $weightKg / 1000;
                $ratePerKm = 150;

                if ($weightTons > 30) {
                    $ratePerKm = 300;
                } elseif ($weightTons > 25) {
                    $ratePerKm = 280;
                } elseif ($weightTons > 20) {
                    $ratePerKm = 250;
                } elseif ($weightTons > 15) {
                    $ratePerKm = 200;
                } elseif ($weightTons > 10) {
                    $ratePerKm = 180;
                }

                \Log::info('📍 DELIVERY: weight='.$weightKg.'кг ('.$weightTons.'т) rate='.$ratePerKm.'р/км');

                $deliveryService = app(DeliveryCalculatorService::class);
                $distanceKm = $deliveryService->calculateDistance(
                    $locationFrom,
                    new \App\Models\Location(['address' => $validated['address']])
                );

                $deliveryCost = $distanceKm * $ratePerKm;

                \Log::info('✅ DELIVERY: '.$distanceKm.'км × '.$ratePerKm.'р = '.$deliveryCost.'р');
            } catch (\Exception $e) {
                \Log::warning('❌ Delivery error: '.$e->getMessage());
            }
        }

        $cart = $this->getCart();
        $item = $cart->items()->create([
            'equipment_id' => $equipment->id,
            'rental_term_id' => $term->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'period_count' => $totalHours,
            'actual_working_hours' => $totalHours,
            'base_price' => $basePricePerHour,
            'platform_fee' => $platformFeePerHour,
            'total_price' => $totalPrice,
            'quantity' => $quantity,
            'shifts_per_day' => $shiftsPerDay,
            'hours_per_shift' => $hoursPerShift,
            'address' => $validated['address'] ?? null,
            'delivery_cost' => round($deliveryCost, 2),
            'distance_km' => round($distanceKm, 2),
        ]);

        $item->load('equipment.rentalTerms', 'equipment.images');
        $this->updateCartTotal($cart);

        return response()->json([
            'success' => true,
            'item' => $item,
            'total' => (float)$cart->total_base_amount,
            'count' => $cart->items()->count(),
        ]);
    }

    public function destroyBatch(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'error' => 'Не указаны ID для удаления'], 400);
        }

        $cart = $this->getCart();
        $deleted = $cart->items()->whereIn('id', $ids)->delete();
        $this->updateCartTotal($cart);

        return response()->json([
            'success' => true,
            'deleted_count' => $deleted,
        ]);
    }

    public function destroy($id)
    {
        $cart = $this->getCart();
        $item = $cart->items()->findOrFail($id);
        $item->delete();
        $this->updateCartTotal($cart);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $cart = $this->getCart();
        $item = $cart->items()->findOrFail($id);

        if ($request->has('quantity')) {
            $item->quantity = max(1, (int)$request->quantity);
        }
        if ($request->has('start_date')) {
            $item->start_date = $request->start_date;
        }
        if ($request->has('end_date')) {
            $item->end_date = $request->end_date;
        }
        if ($request->has('shifts_per_day')) {
            $item->shifts_per_day = min(2, max(1, (int)$request->shifts_per_day));
        }
        if ($request->has('hours_per_shift')) {
            $item->hours_per_shift = max(1, min(24, (int)$request->hours_per_shift));
        }
        if ($request->has('address')) {
            $item->address = $request->address;
        }

        $start = \Carbon\Carbon::parse($item->start_date);
        $end = \Carbon\Carbon::parse($item->end_date);
        $days = $start->diffInDays($end) + 1;
        $hoursPerShift = $item->hours_per_shift ?? 8;
        $shiftsPerDay = $item->shifts_per_day ?? 1;
        $item->period_count = $days * $hoursPerShift * $shiftsPerDay;
        $item->total_price = ($item->base_price + $item->platform_fee) * $item->period_count * $item->quantity;
        $item->save();

        $this->updateCartTotal($cart);
        $item->load('equipment.rentalTerms', 'equipment.images');

        return response()->json([
            'success' => true,
            'item' => $item,
            'total' => (float)$cart->total_base_amount,
        ]);
    }

    protected function getCart()
    {
        $cart = Cart::firstOrCreate(
            ['user_id' => auth()->id(), 'type' => Cart::TYPE_REGULAR],
            ['total_base_amount' => 0, 'total_platform_fee' => 0]
        );
        return $cart;
    }

    protected function updateCartTotal($cart)
    {
        $total = (float)$cart->items->sum('total_price');
        $cart->total_base_amount = $total;
        $cart->save();
    }
}
