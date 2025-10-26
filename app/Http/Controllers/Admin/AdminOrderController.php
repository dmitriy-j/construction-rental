<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;

class AdminOrderController extends Controller
{
    public function show(Order $order)
    {
        // Проверяем что заказ принадлежит арендатору
        abort_unless($order->lesseeCompany->is_lessee, 404);

        $order->load([
            'items.equipment.company',
            'items.rentalTerm',
            'lessorCompany',
            'lesseeCompany',
            'waybills',
            'deliveryNote',
        ]);

        return view('admin.orders.show', compact('order'));
    }
}
