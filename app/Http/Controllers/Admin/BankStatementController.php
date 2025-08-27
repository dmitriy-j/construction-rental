<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\Parsers\BankStatementParser;
use Illuminate\Support\Facades\Log;

class BankStatementController extends Controller
{
    public function index()
    {
        $statements = BankStatement::with('processedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.finance.bank-statements', compact('statements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'statement' => 'required|file|mimes:txt,xml',
            'bank_name' => 'required|string|max:255'
        ]);

        try {
            $file = $request->file('statement');
            $path = $file->store('bank-statements');

            $statement = BankStatement::create([
                'filename' => $path,
                'bank_name' => $request->bank_name,
                'transactions_count' => 0,
                'processed_by' => auth()->id(),
                'status' => 'pending'
            ]);

            // Запускаем обработку в фоне
            dispatch(new \App\Jobs\ProcessBankStatement($statement));

            return redirect()->route('admin.bank-statements.index')
                ->with('success', 'Выписка успешно загружена и поставлена в очередь на обработку');
        } catch (\Exception $e) {
            Log::error('Bank statement upload error', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Ошибка загрузки выписки: ' . $e->getMessage());
        }
    }

    public function show(BankStatement $statement)
    {
        $statement->load('processedBy');
        return view('admin.finance.bank-statement-show', compact('statement'));
    }

    public function destroy(BankStatement $statement)
    {
        try {
            // Удаляем файл
            if (Storage::exists($statement->filename)) {
                Storage::delete($statement->filename);
            }

            $statement->delete();

            return redirect()->route('admin.bank-statements.index')
                ->with('success', 'Выписка успешно удалена');
        } catch (\Exception $e) {
            Log::error('Bank statement delete error', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Ошибка удаления выписки: ' . $e->getMessage());
        }
    }
}
