<?php

namespace App\Http\Controllers;

use App\Services\UPDPdfGenerator;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Contract;

class OrderController extends Controller
{

    public function downloadUPDF(Order $order, $type)
    {
        $generator = new UPDPdfGenerator();

        return match($type) {
            'lessor' => $generator->generateForLessor($order),
            'lessee' => $generator->generateForLessee($order),
            default => abort(404)
        };
    }


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

    public function cancel(Order $order)
    {
        $this->authorize('cancel', $order);
        $order->cancel();
        return redirect()->back()->with('success', 'Заказ успешно отменен');
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
