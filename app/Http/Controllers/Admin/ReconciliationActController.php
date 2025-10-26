<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ReconciliationAct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReconciliationActController extends Controller
{
    public function index()
    {
        $acts = ReconciliationAct::with('company')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.finance.reconciliation-acts', compact('acts'));
    }

    public function create()
    {
        $companies = Company::where('is_lessee', true)
            ->orWhere('is_lessor', true)
            ->get();

        return view('admin.finance.reconciliation-act-create', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $company = Company::findOrFail($request->company_id);

            $act = ReconciliationAct::generate(
                $company,
                $request->period_start,
                $request->period_end
            );

            // Генерируем PDF
            $act->generatePdf();

            return redirect()->route('admin.reconciliation-acts.show', $act)
                ->with('success', 'Акт сверки успешно создан');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка создания акта сверки: '.$e->getMessage());
        }
    }

    public function show(ReconciliationAct $reconciliationAct)
    {
        $reconciliationAct->load('company');

        return view('admin.finance.reconciliation-act-show', compact('reconciliationAct'));
    }

    public function confirm(ReconciliationAct $reconciliationAct)
    {
        try {
            $reconciliationAct->confirmByPlatform();

            return redirect()->back()
                ->with('success', 'Акт сверки подтвержден платформой');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка подтверждения акта: '.$e->getMessage());
        }
    }

    public function download(ReconciliationAct $reconciliationAct)
    {
        if (! Storage::exists($reconciliationAct->file_path)) {
            return redirect()->back()
                ->with('error', 'Файл акта сверки не найден');
        }

        return Storage::download($reconciliationAct->file_path, 'reconciliation_act_'.$reconciliationAct->id.'.pdf');
    }

    public function destroy(ReconciliationAct $reconciliationAct)
    {
        try {
            // Удаляем файл PDF
            if ($reconciliationAct->file_path && Storage::exists($reconciliationAct->file_path)) {
                Storage::delete($reconciliationAct->file_path);
            }

            $reconciliationAct->delete();

            return redirect()->route('admin.reconciliation-acts.index')
                ->with('success', 'Акт сверки успешно удален');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка удаления акта сверки: '.$e->getMessage());
        }
    }
}
