<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Models\EquipmentRentalTerm;
use Illuminate\Http\Request;
use App\Services\PricingService;
use App\Services\EquipmentAvailabilityService;
use App\Services\DeliveryCalculatorService;
use App\Models\RentalCondition;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
            Log::debug('Starting add to cart', [
                'user' => auth()->id(),
                'rental_term' => $rentalTerm->id,
                'request' => $request->all()
            ]);

            // Основные правила валидации
            $rules = [
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'use_default_conditions' => 'sometimes|boolean',
                'delivery_required' => 'required|boolean',
            ];

            if ($request->delivery_required) {
                $rules['delivery_from_id'] = 'required|exists:locations,id';
                $rules['delivery_to_id'] = 'required|exists:locations,id';
            }

            // Дополнительные правила для кастомных условий
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

            if ($validator->fails()) {
                Log::warning('Validation failed', ['errors' => $validator->errors()]);
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
                $rentalCondition = $company->defaultRentalCondition();

                if (!$rentalCondition) {
                    Log::warning('No default rental condition found', ['company_id' => $company->id]);
                    return back()
                        ->withInput()
                        ->with('error', 'Условия по умолчанию не настроены для вашей компании');
                }
            } else {
                $rentalCondition = RentalCondition::create([
                    'company_id' => $company->id,
                    'shift_hours' => $request->shift_hours,
                    'shifts_per_day' => $request->shifts_per_day,
                    'transportation' => $request->transportation,
                    'fuel_responsibility' => $request->fuel_responsibility,
                    'extension_policy' => $request->extension_policy,
                    'payment_type' => $request->payment_type,
                    'is_default' => false
                ]);
                Log::debug('Custom rental condition created', ['condition_id' => $rentalCondition->id]);
            }

            // Рассчитываем количество периодов с учетом условий
            $periodCount = $rentalTerm->calculatePeriodCount(
                $request->start_date,
                $request->end_date,
                $rentalCondition
            );

            Log::debug('Period count calculated', ['count' => $periodCount]);

            // Проверяем минимальный период аренды
            if ($periodCount < $rentalTerm->min_rental_period) {
                Log::warning('Rental period too short', [
                    'min_period' => $rentalTerm->min_rental_period,
                    'calculated_period' => $periodCount
                ]);
                return back()
                    ->withInput()
                    ->with('error', "Минимальный период аренды: {$rentalTerm->min_rental_period} {$rentalTerm->period}");
            }

            // Проверка доступности оборудования
            $availabilityService = app(EquipmentAvailabilityService::class);
            if (!$availabilityService->isAvailable(
                $rentalTerm->equipment,
                $request->start_date,
                $request->end_date
            )) {
                Log::warning('Equipment not available', [
                    'equipment_id' => $rentalTerm->equipment_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date
                ]);
                return back()
                    ->withInput()
                    ->withErrors(['availability' => 'Оборудование недоступно на выбранные даты']);
            }

            // Расчет стоимости
            $pricing = app(PricingService::class)->calculatePrice(
                $rentalTerm,
                $company,
                $periodCount,
                $rentalCondition
            );

            Log::debug('Pricing calculated', $pricing);

            // Расчет стоимости доставки
            $deliveryCost = 0;
            if ($request->delivery_required) {
                // Проверка существования локаций
                $equipmentCompany = $rentalTerm->equipment->company;

                if (!$equipmentCompany || !$equipmentCompany->locations->count()) {
                    Log::error('Lessor locations not found', [
                        'equipment_id' => $rentalTerm->equipment_id,
                        'company_id' => $equipmentCompany->id ?? null
                    ]);
                    return back()
                        ->withInput()
                        ->with('error', 'У арендодателя не настроены локации техники');
                }

                if (!$company->locations->count()) {
                    Log::error('Lessee locations not found', ['company_id' => $company->id]);
                    return back()
                        ->withInput()
                        ->with('error', 'У вашей компании не настроены локации строительных объектов');
                }

                $from = Location::find($request->delivery_from_id);
                $to = Location::find($request->delivery_to_id);

                // Получаем вес оборудования из спецификации
                $weight = $rentalTerm->equipment->specification->weight ?? 0;

                $deliveryCalculator = app(DeliveryCalculatorService::class);
                $deliveryCost = $deliveryCalculator->calculateDeliveryCost(
                    $from,
                    $to,
                    $rentalCondition,
                    $weight
                );

                Log::debug('Delivery cost calculated', [
                    'from' => $from->id,
                    'to' => $to->id,
                    'cost' => $deliveryCost
                ]);

                // Добавляем стоимость доставки к базовой цене
                $pricing['base_price'] += $deliveryCost;
                $pricing['base_price_per_unit'] = $pricing['base_price'] / max(1, $periodCount);
            }

            // Добавляем в корзину
            $this->cartService->addItem(
                $rentalTerm->id,
                $periodCount,
                $pricing['base_price_per_unit'],
                $pricing['platform_fee_per_unit'],
                $request->start_date,
                $request->end_date,
                $rentalCondition->id,
                $request->delivery_required ? $request->delivery_from_id : null,
                $request->delivery_required ? $request->delivery_to_id : null,
                $deliveryCost
            );

            Log::info('Item added to cart', [
                'user_id' => $user->id,
                'rental_term_id' => $rentalTerm->id
            ]);

            return redirect()->route('cart.index')
                ->with('success', 'Оборудование успешно добавлено в корзину');

        } catch (\Exception $e) {
            Log::error('Error adding to cart: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Ошибка при добавлении в корзину: ' . $e->getMessage());
        }
    }

    public function remove($itemId)
    {
        $item = CartItem::findOrFail($itemId);

        // Отменяем временное резервирование
        app(EquipmentAvailabilityService::class)->cancelUserReserves(auth()->id());

        $this->cartService->removeItem($itemId);

        return back()->with('success', 'Позиция удалена из корзины');
    }

    public function updateDates(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date'
        ]);

        $this->cartService->setDates($request->start_date, $request->end_date);
        return back()->with('success', 'Даты аренды обновлены');
    }

    public function clear()
    {
        $this->cartService->clearCart();
        return back()->with('success', 'Корзина очищена');
    }
}
