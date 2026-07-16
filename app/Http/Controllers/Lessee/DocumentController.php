<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\CompletionAct;
use App\Models\Contract;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Upd;
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
                $documents = Waybill::where('perspective', 'lessee')
                    ->whereHas('parentOrder', function ($query) use ($companyId) {
                        $query->where('lessee_company_id', $companyId);
                    })->with(['equipment', 'parentOrder.lessorCompany'])->paginate(10);
                break;

            case 'completion_acts':
                $documents = CompletionAct::where('perspective', 'lessee')
                    ->whereHas('parentOrder', function ($query) use ($companyId) {
                        $query->where('lessee_company_id', $companyId);
                    })->with(['waybill.equipment', 'parentOrder.lessorCompany'])->paginate(10);
                break;

            case 'delivery_notes':
                $documents = DeliveryNote::whereHas('order', function ($query) use ($companyId) {
                    $query->where('lessee_company_id', $companyId);
                })->with(['senderCompany', 'order'])->paginate(10);
                break;

            case 'invoices':
                $documents = Invoice::where('company_id', $companyId)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
                break;

            case 'upds':
                $documents = Upd::where('lessee_company_id', $companyId)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
                break;

            case 'contracts':
            default:
                $documents = Contract::with(['platformCompany', 'counterpartyCompany'])
                    ->where('counterparty_company_id', $companyId)
                    ->where('counterparty_type', 'lessee')
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
                break;
        }

        return view('lessee.documents.index', compact('documents', 'type'));
    }

    public function waybills(Order $order)
    {
        if ($order->lessee_company_id !== auth()->user()->company_id) abort(403);
        $waybills = $order->waybills()->with(['equipment', 'operator', 'orderItem'])->get()->map->forLessee();
        return view('lessee.documents.waybills', compact('order', 'waybills'));
    }

    public function completionActs(Order $order)
    {
        if ($order->lessee_company_id !== auth()->user()->company_id) abort(403);
        $acts = $order->completionActs()->with(['waybill.equipment', 'waybill.operator'])->get()->map(fn($a) => $a->forLessee());
        return view('lessee.documents.completion_acts', compact('order', 'acts'));
    }

    public function downloadWaybill(Waybill $waybill)
    {
        if ($waybill->order->lessee_company_id !== auth()->user()->company_id) abort(403);
        $pdf = PDF::loadView('lessee.documents.waybill_pdf', ['waybill' => $waybill->forLessee()]);
        return $pdf->download("Путевой-лист-{$waybill->number}-для-арендатора.pdf");
    }

    public function downloadCompletionAct(CompletionAct $completionAct)
    {
        if ($completionAct->perspective !== 'lessee' || $completionAct->parentOrder->lessee_company_id !== auth()->user()->company_id) abort(403);
        $completionAct->load(['parentOrder.lesseeCompany', 'waybill.equipment', 'order.items']);
        $pdf = PDF::loadView('lessee.documents.completion_act_pdf', ['act' => $completionAct->forLessee(), 'completionAct' => $completionAct]);
        return $pdf->download("Акт-{$completionAct->number}-для-арендатора.pdf");
    }

    public function showWaybill(Waybill $waybill)
    {
        if ($waybill->perspective !== 'lessee' || $waybill->parentOrder->lessee_company_id !== auth()->user()->company_id) abort(403);
        $waybill->load('shifts', 'order.lessorCompany', 'equipment', 'operator');
        return view('lessee.documents.waybills.show', compact('waybill'));
    }

    public function showCompletionAct(CompletionAct $completionAct)
    {
        if ($completionAct->perspective !== 'lessee' || $completionAct->parentOrder->lessee_company_id !== auth()->user()->company_id) abort(403);
        $completionAct->load(['waybill.shifts', 'waybill.equipment', 'waybill.operator', 'parentOrder.lessorCompany', 'order.items']);
        return view('lessee.documents.completion_acts.show', compact('completionAct'));
    }

    public function showDeliveryNote(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->order->lessee_company_id !== auth()->user()->company_id) abort(403);
        $deliveryNote->load(['senderCompany', 'receiverCompany', 'equipment']);
        return view('lessee.documents.delivery_notes.show', compact('deliveryNote'));
    }
}
