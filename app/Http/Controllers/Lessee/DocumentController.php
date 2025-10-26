<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\CompletionAct;
use App\Models\DeliveryNote;
use App\Models\Order;
use App\Models\Waybill;
use Illuminate\Http\Request;
use PDF;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'contracts');
        $companyId = auth()->user()->company_id;

        switch ($type) {
            case 'waybills':
                $documents = Waybill::where('perspective', 'lessee') // Только документы для арендатора
                    ->whereHas('parentOrder', function ($query) use ($companyId) {
                        $query->where('lessee_company_id', $companyId);
                    })->with(['equipment', 'parentOrder.lessorCompany'])->paginate(10);
                break;

            case 'completion_acts':
                $documents = CompletionAct::where('perspective', 'lessee') // Только документы для арендатора
                    ->whereHas('parentOrder', function ($query) use ($companyId) {
                        $query->where('lessee_company_id', $companyId);
                    })->with(['waybill.equipment', 'parentOrder.lessorCompany'])->paginate(10);
                break;

            case 'delivery_notes':
                $documents = DeliveryNote::whereHas('order', function ($query) use ($companyId) {
                    $query->where('lessee_company_id', $companyId);
                })->with(['senderCompany', 'order'])->paginate(10);
                break;

            case 'contracts':
            default:
                $documents = collect();
                break;
        }

        return view('lessee.documents.index', compact('documents', 'type'));
    }

    public function waybills(Order $order)
    {
        // Проверка прав доступа
        if ($order->lessee_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $waybills = $order->waybills()
            ->with(['equipment', 'operator', 'orderItem'])
            ->get()
            ->map
            ->forLessee();

        return view('lessee.documents.waybills', compact('order', 'waybills'));
    }

    public function completionActs(Order $order)
    {
        if ($order->lessee_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $acts = $order->completionActs()
            ->with(['waybill.equipment', 'waybill.operator'])
            ->get()
            ->map(function ($act) {
                return $act->forLessee();
            });

        return view('lessee.documents.completion_acts', compact('order', 'acts'));
    }

    public function downloadWaybill(Waybill $waybill)
    {
        if ($waybill->order->lessee_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $waybillData = $waybill->forLessee();

        $pdf = PDF::loadView('lessee.documents.waybill_pdf', [
            'waybill' => $waybillData,
        ]);

        return $pdf->download("Путевой-лист-{$waybill->number}-для-арендатора.pdf");
    }

    public function downloadCompletionAct(CompletionAct $completionAct)
    {
        if ($completionAct->order->lessee_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $actData = $completionAct->forLessee();

        $pdf = PDF::loadView('lessee.documents.completion_act_pdf', [
            'act' => $actData,
        ]);

        return $pdf->download("Акт-{$completionAct->id}-для-арендатора.pdf");
    }

    public function showWaybill(Waybill $waybill)
    {
        if ($waybill->perspective !== 'lessee') {
            abort(403, 'Доступ запрещен. Неверный тип документа.');
        }

        if ($waybill->parentOrder->lessee_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен. Документ не принадлежит вашей компании.');
        }

        $waybill->load('shifts', 'order.lessorCompany', 'equipment', 'operator');
        $waybillData = $waybill->forLessee();

        return view('lessee.documents.waybills.show', compact('waybillData', 'waybill'));
    }

    public function showCompletionAct(CompletionAct $completionAct)
    {
        if ($completionAct->perspective !== 'lessee') {
            abort(403, 'Доступ запрещен. Неверный тип документа.');
        }

        if ($completionAct->parentOrder->lessee_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен. Документ не принадлежит вашей компании.');
        }

        $completionAct->load('waybill.shifts', 'order.lessorCompany');
        $actData = $completionAct->forLessee();

        return view('lessee.documents.completion_acts.show', compact('actData', 'completionAct'));
    }

    public function showDeliveryNote(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->order->lessee_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $deliveryNote->load(['senderCompany', 'receiverCompany', 'equipment']);

        return view('lessee.documents.delivery_notes.show', compact('deliveryNote'));
    }
}
