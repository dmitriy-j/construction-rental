<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompletionAct;
use App\Models\Contract;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Upd;
use App\Models\Waybill;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'contracts');

        $documents = [];
        $stats = [
            'contracts' => Contract::count(),
            'delivery_notes' => DeliveryNote::count(),
            'waybills' => Waybill::count(),
            'completion_acts' => CompletionAct::count(),
            'upds' => Upd::count(),
            'invoices' => Invoice::count(),
        ];

        switch ($type) {
            case 'contracts':
                // ОБНОВЛЕНО: Загружаем правильные отношения
                $documents = Contract::with(['platformCompany', 'counterpartyCompany'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            case 'delivery_notes':
                $documents = DeliveryNote::with(['order', 'carrierCompany'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            case 'waybills':
                $documents = Waybill::with(['order', 'operator'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            case 'completion_acts':
                $documents = CompletionAct::with(['order', 'upd'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            case 'upds':
                $documents = Upd::with(['order', 'lessorCompany', 'lesseeCompany'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            case 'invoices':
                $documents = Invoice::with(['order', 'company'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;
        }

        return view('admin.documents.index', compact('documents', 'type', 'stats'));
    }

    public function show($type, $id)
    {
        switch ($type) {
            case 'contracts':
                // ОБНОВЛЕНО: Загружаем правильные отношения
                $document = Contract::with(['platformCompany', 'counterpartyCompany'])->findOrFail($id);
                return view('admin.documents.contracts.show', compact('document'));

            case 'delivery_notes':
                $document = DeliveryNote::with(['order', 'carrierCompany'])->findOrFail($id);
                return view('admin.documents.delivery_notes.show', compact('document'));

            case 'waybills':
                $document = Waybill::with(['order', 'operator', 'shifts'])->findOrFail($id);
                return view('admin.documents.waybills.show', compact('document'));

            case 'completion_acts':
                $document = CompletionAct::with(['order'])->findOrFail($id);
                return view('admin.documents.completion_acts.show', compact('document'));

            case 'upds':
                $document = Upd::with(['order', 'lessorCompany', 'lesseeCompany', 'items'])->findOrFail($id);
                return view('admin.documents.upds.show', compact('document'));

            case 'invoices':
                $document = Invoice::with(['order', 'company'])->findOrFail($id);
                return view('admin.documents.invoices.show', compact('document'));

            default:
                abort(404);
        }
    }
}
