<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Equipment;
use Illuminate\Http\Request;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('lessee_company_id', auth()->user()->company_id)
            ->with(['lessorCompany', 'items.equipment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['items.equipment', 'lessorCompany', 'lesseeCompany']);
        return view('orders.show', compact('order'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'equipment' => 'required|array|min:1',
            'equipment.*.id' => 'required|exists:equipment,id',
            'equipment.*.term_id' => 'required|exists:equipment_rental_terms,id',
        ]);

        $service = app(EquipmentAvailabilityService::class);
        $user = auth()->user();
        $totalAmount = 0;
        $items = [];

        // Проверка доступности и расчет стоимости
        foreach ($request->equipment as $item) {
            $equipment = Equipment::findOrFail($item['id']);
            $term = $equipment->rentalTerms()->findOrFail($item['term_id']);

            if (!$service->isAvailable($equipment, $request->start_date, $request->end_date)) {
                return back()->withErrors("Оборудование {$equipment->title} недоступно на выбранные даты");
            }

            $days = \Carbon\Carbon::parse($request->start_date)
                ->diffInDays($request->end_date) + 1;

            $items[] = [
                'equipment' => $equipment,
                'term' => $term,
                'price' => $term->price * $days
            ];

            $totalAmount += $term->price * $days;
        }

        // Создание заказа
        $order = Order::create([
            'lessee_company_id' => $user->company_id,
            'lessor_company_id' => $items[0]['equipment']->company_id, // Предполагаем одного арендодателя
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => $totalAmount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
        ]);

        // Создание позиций и бронирование
        foreach ($items as $item) {
            $order->items()->create([
                'equipment_id' => $item['equipment']->id,
                'rental_term_id' => $item['term']->id,
                'quantity' => 1,
                'price_per_unit' => $item['term']->price,
                'total_price' => $item['price'],
            ]);

            $service->bookEquipment(
                $item['equipment'],
                $request->start_date,
                $request->end_date,
                $order->id,
                'booked'
            );
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Заказ успешно создан!');
    }

    public function cancel(Order $order)
    {
        $this->authorize('cancel', $order);

        $order->cancel();

        return redirect()->back()
            ->with('success', 'Заказ успешно отменен');
    }

    public function requestExtension(Order $order, Request $request)
    {
        $this->authorize('update', $order);

        $request->validate([
            'new_end_date' => 'required|date|after:'.$order->end_date->format('Y-m-d')
        ]);

        $service = app(EquipmentAvailabilityService::class);
        $newEndDate = $request->new_end_date;

        // Проверка доступности оборудования на новые даты
        foreach ($order->items as $item) {
            if (!$service->isAvailable(
                $item->equipment,
                $order->end_date->addDay()->format('Y-m-d'),
                $newEndDate
            )) {
                return back()->withErrors("Оборудование {$item->equipment->title} недоступно на выбранные даты");
            }
        }

        // Сохраняем запрос на продление
        $order->update([
            'extension_requested' => true,
            'requested_end_date' => $newEndDate,
            'status' => Order::STATUS_EXTENSION_REQUESTED
        ]);

        return back()->with('success', 'Запрос на продление отправлен арендодателю');
    }
}
