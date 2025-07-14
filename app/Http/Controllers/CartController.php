<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Models\EquipmentRentalTerm;
use Illuminate\Http\Request;
use App\Services\PricingService;
use App\Models\Equipment;
use App\Services\EquipmentAvailabilityService;
use App\Services\DeliveryCalculatorService;
use App\Models\RentalCondition;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem;
use App\Services\TransportCalculatorService;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        $cart = $this->cartService->getCart();
        $cart->load([
            'items.rentalTerm.equipment',
            'items.rentalCondition',
            'items.deliveryFrom',
            'items.deliveryTo'
        ]);

        return view('lessee.cart.index', [
            'cart' => $cart,
            'total' => $cart->total_base_amount + $cart->total_platform_fee - $cart->discount_amount
        ]);
    }

    public function add(EquipmentRentalTerm $rentalTerm, Request $request)
    {
        try {
            // Преобразование чекбоксов в boolean
            $request->merge([
                'delivery_required' => (bool)$request->input('delivery_required', false),
                'use_default_conditions' => $request->has('use_default_conditions')
            ]);

            \Log::debug('[CART DEBUG] Start add method', [
                'user' => auth()->id(),
                'equipment' => $rentalTerm->id,
                'request' => $request->all(),
                'locations' => auth()->user()->company->locations->pluck('id')
            ]);

            // Основные правила валидации
            $rules = [
                'start_date' => 'required|date|after_or_equal:today|before_or_equal:'.now()->addYear(),
                'end_date' => 'required|date|after:start_date|before_or_equal:'.now()->addYear(),
                'use_default_conditions' => 'sometimes|boolean',
                'delivery_required' => 'required|boolean',
            ];

            if ($request->delivery_required) {
                $rules['delivery_from_id'] = 'required|exists:locations,id';
                $rules['delivery_to_id'] = 'required|exists:locations,id';
            }

            if (!$request->input('use_default_conditions', false)) {
                $rules = array_merge($rules, [
                    'shift_hours' => 'required|integer|min:1|max:24',
                    'shifts_per_day' => 'required|integer|min:1|max:3',
                    'transportation' => [
                        'required',
                        Rule::in(['lessor', 'lessee', 'shared'])
                    ],
                    'fuel_responsibility' => [
                        'required',
                        Rule::in(['lessor', 'lessee'])
                    ],
                    'extension_policy' => [
                        'required',
                        Rule::in(['allowed', 'not_allowed', 'conditional'])
                    ],
                    'payment_type' => [
                        'required',
                        Rule::in(['hourly', 'shift', 'daily', 'mileage', 'volume'])
                    ]
                ]);
            }

            $validator = Validator::make($request->all(), $rules);

            \Log::debug('[CART DEBUG] Before validation');

            if ($validator->fails()) {
                \Log::debug('[CART DEBUG] Validation failed', $validator->errors()->toArray());
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Пожалуйста, исправьте ошибки в форме');
            }

            Log::debug('Validation passed');

            $user = auth()->user();
            $company = $user->company;

            if (!$company) {
                Log::error('User company not found', ['user_id' => $user->id]);
                return back()->with('error', 'Ваша компания не найдена');
            }

            // Получаем условия аренды
            if ($request->use_default_conditions) {
                $rentalCondition = RentalCondition::where('company_id', $company->id)
                    ->where('is_default', true)
                    ->first();

                if (!$rentalCondition) {
                    Log::error('Default rental condition not found', ['company_id' => $company->id]);
                    return back()->with('error', 'Условия аренды по умолчанию не настроены для вашей компании');
                }
            } else {
                $rentalCondition = RentalCondition::firstOrCreate([
                    'company_id' => $company->id,
                    'shift_hours' => $request->shift_hours,
                    'shifts_per_day' => $request->shifts_per_day,
                    'transportation' => $request->transportation,
                    'fuel_responsibility' => $request->fuel_responsibility,
                    'extension_policy' => $request->extension_policy,
                    'payment_type' => $request->payment_type,
                    'is_default' => false
                ]);
            }

            Log::debug('Rental condition resolved', ['condition_id' => $rentalCondition->id]);

            // Рассчитываем количество часов аренды
            $hoursCount = $this->calculateHoursCount(
                $request->start_date,
                $request->end_date
            );

            Log::debug('Hours count calculated', ['count' => $hoursCount]);

            // Проверка минимального периода аренды
            if ($hoursCount < $rentalTerm->min_rental_hours) {
                return back()
                    ->withInput()
                    ->with('error', "Минимальный период аренды: {$rentalTerm->min_rental_hours} часов");
            }

            // Проверка доступности оборудования
            $availabilityService = app(EquipmentAvailabilityService::class);
            if (!$availabilityService->isAvailable(
                $rentalTerm->equipment,
                Carbon::parse($request->start_date)->toDateString(),
                Carbon::parse($request->end_date)->toDateString()
            )) {
                $nextAvailable = $rentalTerm->equipment->next_available_date;
                $message = $nextAvailable
                    ? "Оборудование недоступно. Ближайшая доступная дата: ".$nextAvailable->format('d.m.Y')
                    : "Оборудование недоступно на выбранные даты";

                return back()
                    ->withInput()
                    ->with('error', $message);
            }

            // Расчет стоимости
            $pricing = app(PricingService::class)->calculatePrice(
                $rentalTerm,
                $company,
                $hoursCount,
                $rentalCondition
            );

            // Принудительное преобразование типов
            $pricing['base_price_per_unit'] = (float)$pricing['base_price_per_unit'];
            $pricing['platform_fee_per_unit'] = (float)$pricing['platform_fee_per_unit'];

            Log::debug('Pricing calculated', $pricing);

            // Расчет стоимости доставки
            $deliveryCost = 0;
            if ($request->delivery_required) {
                $equipmentCompany = $rentalTerm->equipment->company;

                if (!$equipmentCompany || !$equipmentCompany->locations->count()) {
                    return back()
                        ->withInput()
                        ->with('error', 'У арендодателя не настроены локации техники');
                }

                if (!$company->locations->count()) {
                    return back()
                        ->withInput()
                        ->with('error', 'У вашей компании не настроены локации строительных объектов');
                }

                $from = Location::find($request->delivery_from_id);
                $to = Location::find($request->delivery_to_id);

                $deliveryCalculator = app(DeliveryCalculatorService::class);
                $distance = $deliveryCalculator->calculateDistance(
                    $from->latitude,
                    $from->longitude,
                    $to->latitude,
                    $to->longitude
                );

                $equipment = Equipment::with(['specifications' => function ($query) {
                    $query->select('equipment_id', 'key', 'weight', 'length', 'width', 'height');
                }])->find($rentalTerm->equipment_id);

                if (!$equipment) {
                    throw new \Exception("Оборудование не найдено");
                }

                $weight = 5000;
                if ($equipment && $equipment->specifications) {
                    foreach ($equipment->specifications as $spec) {
                        if ($spec->key === 'Вес' && $spec->weight) {
                            $weight = $spec->weight;
                            break;
                        }
                    }
                }

                $transportService = app(TransportCalculatorService::class);
                $vehicleType = $transportService->calculateRequiredTransport($equipment);
                $deliveryCost = $transportService->getTransportRate($vehicleType) * $distance;

                $pricing['base_price'] += $deliveryCost;
                $pricing['base_price_per_unit'] = $pricing['base_price'] / max(1, $hoursCount);
            }

            // Защита от нулевых цен
            if ($pricing['base_price_per_unit'] === null) {
                throw new \Exception('Базовая цена не может быть null');
            }

            $cart = $this->cartService->getCart();
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('rental_term_id', $rentalTerm->id)
                ->first();

            if ($existingItem) {
                return redirect()->route('cart.index')
                    ->with('warning', 'Это оборудование уже в вашей корзине');
            }

            \Log::debug('[CART DEBUG] Before saving to cart');

            // Добавляем в корзину
            $this->cartService->addItem(
                $rentalTerm->id,
                $hoursCount,
                $pricing['base_price_per_unit'],
                $pricing['platform_fee_per_unit'],
                $request->start_date,
                $request->end_date,
                $rentalCondition->id,
                $request->delivery_required ? $request->delivery_from_id : null,
                $request->delivery_required ? $request->delivery_to_id : null,
                $deliveryCost
            );

            return redirect()->route('cart.index')
                ->with('success', 'Оборудование успешно добавлено в корзину');

        } catch (\Exception $e) {
            \Log::debug('[CART DEBUG] Exception caught', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Ошибка при добавлении в корзину: ' . $e->getMessage());
        }
    }

    protected function calculateHoursCount($startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        return (int) ceil($end->diffInHours($start));
    }

    public function remove($itemId)
    {
        $item = CartItem::findOrFail($itemId);

        if ($item->cart->user_id !== auth()->id()) {
            abort(403, 'Недостаточно прав для удаления этого элемента');
        }

        app(EquipmentAvailabilityService::class)->cancelUserReserves(auth()->id());
        $this->cartService->removeItem($itemId);

        return back()->with('success', 'Позиция удалена из корзины');
    }

    public function updateDates(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date'
        ]);

        $this->cartService->setDates(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );
        return back()->with('success', 'Даты аренды обновлены');
    }

    public function clear()
    {
        $this->cartService->clearCart();
        return back()->with('success', 'Корзина очищена');
    }
}
