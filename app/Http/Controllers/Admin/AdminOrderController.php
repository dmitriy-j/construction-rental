<?php
// app/Http/Controllers/Admin/AdminOrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderRecalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminOrderController extends Controller
{
    protected $recalculationService;

    public function __construct(OrderRecalculationService $recalculationService)
    {
        $this->recalculationService = $recalculationService;
    }

    /**
     * Список всех заказов в админке
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'lesseeCompany',
            'lessorCompany',
            'items.equipment',
            'childOrders.items' // ДОБАВЛЕНО: загружаем дочерние заказы с позициями
        ])
        ->whereNull('parent_order_id') // Только родительские заказы
        ->latest();

        // Фильтрация по статусу
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Фильтрация по дате
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);

        // ПЕРЕСЧИТЫВАЕМ СУММЫ ДЛЯ АГРЕГИРОВАННЫХ ЗАКАЗОВ
        $orders->getCollection()->transform(function ($order) {
            if ($order->isParent() && $order->childOrders->count() > 0) {
                // Рассчитываем суммы из дочерних заказов
                $allItems = $order->childOrders->flatMap->items;

                $calculatedBaseAmount = $allItems->sum(function($item) {
                    return ($item->fixed_lessor_price ?? $item->base_price) * $item->period_count;
                });

                $calculatedPlatformFee = $allItems->sum('platform_fee');
                $calculatedTotalAmount = $allItems->sum('total_price');
                $calculatedDeliveryCost = $order->childOrders->sum('delivery_cost');

                // Добавляем вычисленные суммы как дополнительные атрибуты
                $order->calculated_base_amount = $calculatedBaseAmount;
                $order->calculated_total_amount = $calculatedTotalAmount;
                $order->calculated_platform_fee = $calculatedPlatformFee;
                $order->calculated_delivery_cost = $calculatedDeliveryCost;
                $order->calculated_lessor_payout = $calculatedBaseAmount + $calculatedDeliveryCost;
            } else {
                // Для обычных заказов используем существующие суммы
                $order->calculated_base_amount = $order->base_amount;
                $order->calculated_total_amount = $order->total_amount;
                $order->calculated_platform_fee = $order->platform_fee;
                $order->calculated_delivery_cost = $order->delivery_cost;
                $order->calculated_lessor_payout = $order->lessor_payout;
            }

            return $order;
        });

        // Статистика для фильтров
        $statuses = Order::statuses();
        $totalOrders = Order::whereNull('parent_order_id')->count();
        $pendingOrders = Order::whereNull('parent_order_id')
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PENDING_APPROVAL])
            ->count();
        $activeOrders = Order::whereNull('parent_order_id')
            ->where('status', Order::STATUS_ACTIVE)
            ->count();
        $completedOrders = Order::whereNull('parent_order_id')
            ->where('status', Order::STATUS_COMPLETED)
            ->count();

        return view('admin.orders.index', compact(
            'orders',
            'statuses',
            'totalOrders',
            'pendingOrders',
            'activeOrders',
            'completedOrders'
        ));
    }

    public function show(Order $order)
    {
        // Проверяем что заказ принадлежит арендатору
        abort_unless($order->lesseeCompany->is_lessee, 404);

        $order->load([
            'items.equipment.company',
            'items.rentalTerm',
            'items.rentalCondition',
            'lessorCompany',
            'lesseeCompany',
            'waybills',
            'deliveryNote',
            'childOrders.items.equipment.company',
            'childOrders.items.rentalTerm',
            'childOrders.lessorCompany',
        ]);

        // Собираем ВСЕ позиции (из родительского + дочерних заказов)
        $allItems = collect();

        if ($order->isParent()) {
            // Для родительского заказа берем позиции из всех дочерних
            $allItems = $order->childOrders->flatMap->items;
        } else {
            // Для дочернего заказа берем его собственные позиции
            $allItems = $order->items;
        }

        // Рассчитываем финансовые показатели
        $calculatedBaseAmount = $allItems->sum(function($item) {
            return ($item->fixed_lessor_price ?? $item->base_price) * $item->period_count;
        });

        $calculatedPlatformFee = $allItems->sum('platform_fee');
        $calculatedTotalAmount = $allItems->sum('total_price');

        return view('admin.orders.show', compact(
            'order',
            'allItems',
            'calculatedBaseAmount',
            'calculatedPlatformFee',
            'calculatedTotalAmount'
        ));
    }

    /**
     * Форма изменения дат заказа
     */
    public function editDates(Order $order)
    {
        return view('admin.orders.edit_dates', compact('order'));
    }

    /**
     * Проверка доступности оборудования на новые даты
     */
    public function checkDatesAvailability(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $availability = $this->recalculationService->checkAvailability(
            $order,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json($availability);
    }

    /**
     * Обновление дат заказа с пересчетом
     */
    public function updateDates(Request $request, Order $order)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'confirm_availability' => 'sometimes|boolean',
        ]);

        // Если не подтверждена доступность - проверяем
        if (!$request->confirm_availability) {
            $availability = $this->recalculationService->checkAvailability(
                $order,
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date)
            );

            if (!$availability['available']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'dates' => 'Оборудование недоступно на выбранные даты: ' .
                            collect($availability['unavailable_equipment'])
                                ->pluck('equipment')
                                ->implode(', ')
                    ])
                    ->with('availability_check', $availability);
            }
        }

        $result = $this->recalculationService->recalculateOrderDates(
            $order,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Даты заказа успешно изменены. Суммы пересчитаны.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * Принудительное обновление дат без проверки доступности
     */
    public function forceUpdateDates(Request $request, Order $order)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $result = $this->recalculationService->recalculateOrderDates(
            $order,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Даты заказа принудительно изменены. Суммы пересчитаны.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result['message']);
    }
}
