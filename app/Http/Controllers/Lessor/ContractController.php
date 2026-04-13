<?php
namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $contracts = Contract::with(['platformCompany', 'counterpartyCompany'])
            ->where('counterparty_company_id', $companyId)
            ->where('counterparty_type', 'lessor')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('lessor.documents.contracts.index', compact('contracts'));
    }

    public function show(Contract $contract)
    {
        // Проверяем, что договор принадлежит компании пользователя
        if ($contract->counterparty_company_id !== Auth::user()->company_id ||
            $contract->counterparty_type !== 'lessor') {
            abort(403, 'Доступ запрещен');
        }

        $contract->load(['platformCompany', 'counterpartyCompany']);
        return view('lessor.documents.contracts.show', compact('contract'));
    }

    public function download(Contract $contract)
    {
        // Проверяем, что договор принадлежит компании пользователя
        if ($contract->counterparty_company_id !== Auth::user()->company_id ||
            $contract->counterparty_type !== 'lessor') {
            abort(403, 'Доступ запрещен');
        }

        if (!$contract->file_path) {
            abort(404, 'Файл договора не найден');
        }

        return response()->download(storage_path('app/public/' . $contract->file_path));
    }
}
