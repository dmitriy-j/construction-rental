<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\CompletionAct;
use Illuminate\Http\Request;

class CompletionActController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $companyId = auth()->user()->company_id;

        $acts = CompletionAct::where('perspective', 'lessor') // Фильтруем по перспективе
            ->whereHas('order', function ($query) use ($companyId) {
                $query->where('lessor_company_id', $companyId);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->with(['order', 'waybill.equipment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('lessor.documents.completion_acts.index', compact('acts'));
    }

    public function show(CompletionAct $completionAct)
    {
        if ($completionAct->perspective !== 'lessor' ||
            $completionAct->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $completionAct->load([
            'order.items.equipment',
            'waybill.shifts',
            'waybill.equipment',
            'waybill.operator',
        ]);

        return view('lessor.documents.completion_acts.show', compact('completionAct'));
    }

    public function download(CompletionAct $completionAct)
    {
        if ($completionAct->perspective !== 'lessor' ||
            $completionAct->order->lessor_company_id !== auth()->user()->company_id) {
            abort(403, 'Доступ запрещен');
        }

        $completionAct->load([
            'order.lesseeCompany',
            'order.lessorCompany',
            'waybill.equipment',
            'waybill.operator',
        ]);

        $pdf = PDF::loadView('lessor.documents.completion_acts.pdf', compact('completionAct'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Акт-выполненных-работ-{$completionAct->number}.pdf");
    }
}
