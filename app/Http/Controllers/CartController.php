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

        // Правильная загрузка отношений с учетом изображений
        $cart->load([
            'items.rentalTerm.equipment.mainImage',
            'items.rentalCondition',
            'items.deliveryFrom',
            'items.deliveryTo'
        ]);

        // Рассчитываем итоговые суммы
        $totalRental = $cart->items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $totalDelivery = $cart->items->sum('delivery_cost');
        $grandTotal = $totalRental + $totalDelivery;

        return view('lessee.cart.index', [
            'cart' => $cart,
            'totalRental' => $totalRental,
            'totalDelivery' => $totalDelivery,
            'grandTotal' => $grandTotal
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
                $rules['delivery_location_id'] = 'required|exists:locations,id';
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
            $company = $user->company; // Компания арендатора

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
                    'delivery_location_id' => $request->delivery_location_id,
                    'is_default' => false
                ]);
            }

            Log::debug('Rental condition resolved', ['condition_id' => $rentalCondition->id]);

            // Рассчитываем количество дней аренды (с 15.07 по 16.07 = 1 день)
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);
            $days = $end->diffInDays($start); // 1 день для периода 15-16

            // Рассчитываем общее количество рабочих часов
            $workingHours = $days * $rentalCondition->shift_hours * $rentalCondition->shifts_per_day;

            Log::debug('Working hours calculated', [
                'days' => $days,
                'shift_hours' => $rentalCondition->shift_hours,
                'shifts_per_day' => $rentalCondition->shifts_per_day,
                'total_hours' => $workingHours
            ]);

            // Проверка минимального периода аренды
            if ($workingHours < $rentalTerm->min_rental_hours) {
                return back()
                    ->withInput()
                    ->with('error', "Минимальный период аренды: {$rentalTerm->min_rental_hours} часов");
            }

            // Проверка доступности оборудования
            $availabilityService = app(EquipmentAvailabilityService::class);
            if (!$availabilityService->isAvailable(
                $rentalTerm->equipment,
                $start->toDateString(),
                $end->toDateString()
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
                $workingHours,
                $rentalCondition
            );

            Log::debug('Pricing calculated', $pricing);

            // Расчет стоимости доставки
            $deliveryCost = 0;
            if ($request->delivery_required) {
                $equipmentCompany = $rentalTerm->equipment->company;

                // Проверка локаций арендодателя
                if (!$equipmentCompany || !$equipmentCompany->locations->count()) {
                    return back()
                        ->withInput()
                        ->with('error', 'У арендодателя не настроены локации техники');
                }

                // Проверка локаций арендатора
                if (!$company->activeRentalConditions->count()) {
                    return back()
                        ->withInput()
                        ->with('error', 'У вашей компании не настроены строительные площадки');
                }

                // Получаем локации из запроса
                $from = Location::find($request->delivery_from_id);
                $to = Location::find($request->delivery_location_id);

                // Проверка существования локаций
                if (!$from || !$to) {
                    return back()
                        ->withInput()
                        ->with('error', 'Указанные локации не найдены');
                }

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

                $transportService = app(TransportCalculatorService::class);
                $vehicleType = $transportService->calculateRequiredTransport($equipment);
                $deliveryCost = $transportService->getTransportRate($vehicleType) * $distance;
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

            \Log::debug('[CART DEBUG] Before saving to cart', [
                'rental_term_id' => $rentalTerm->id,
                'period_count' => $workingHours,
                'base_price' => $pricing['base_price_per_unit'],
                'platform_fee' => $pricing['platform_fee'],
                'delivery_cost' => $deliveryCost
            ]);

            // Добавляем в корзину
            $this->cartService->addItem(
                $rentalTerm->id,
                $workingHours, // Количество рабочих часов
                $pricing['base_price_per_unit'], // Чистая цена аренды за час
                $pricing['platform_fee'], // Платформенная комиссия
                $request->start_date,
                $request->end_date,
                $rentalCondition->id,
                $request->delivery_required ? $request->delivery_from_id : null,
                $request->delivery_required ? $request->delivery_location_id : null,
                $deliveryCost // Сохраняем доставку отдельно
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

    public function remove($itemId)
    {
        $item = CartItem::findOrFail($itemId);

        if ($item->cart->user_id !== auth()->id()) {
            abort(403, 'Недостаточно прав для удаления этого элемента');
        }


        $this->cartService->removeItem($itemId);

        return back()->with('success', 'Позиция удалена из корзины');
    }

    public function updateDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $selectedItems = json_decode($request->input('selected_items', '[]'), true);

        if (empty($selectedItems)) {
            return back()->with('error', 'Выберите элементы для обновления');
        }

        $this->cartService->updateSelectedItems(
            $selectedItems,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return back()->with('success', 'Даты аренды обновлены для выбранных позиций');
    }
    public function clear()
    {
        $this->cartService->clearCart();
        return back()->with('success', 'Корзина очищена');
    }

    public function removeSelected(Request $request)
    {
        \Log::debug('Remove selected items', ['request' => $request->all()]);

        $request->validate([
            'items' => 'required|array',
            'items.*' => 'exists:cart_items,id'
        ]);

        $itemIds = $request->input('items');
        \Log::debug('Items to delete', ['ids' => $itemIds]);

        $cart = $this->cartService->getCart();
        \Log::debug('User cart', ['cart_id' => $cart->id, 'user_id' => auth()->id()]);

        $deleted = $cart->items()->whereIn('id', $itemIds)->delete();
        \Log::debug('Deleted items', ['count' => $deleted]);

        $this->cartService->recalculateTotals($cart);

        return response()->json([
            'success' => true,
            'deleted_count' => $deleted
        ]);
    }
}
