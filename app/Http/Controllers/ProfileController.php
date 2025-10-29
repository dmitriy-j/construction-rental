<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateCompanyBankDetailsRequest; // 🔥 ДОБАВЬТЕ ЭТОТ ИМПОРТ
use App\Models\BankDetailsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // 🔥 ДОБАВЬТЕ ЭТОТ ИМПОРТ
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        \Log::info('ProfileController edit called', ['user_id' => $request->user()->id]);

        $user = $request->user()->load(['company', 'roles']);

        \Log::info('User company data:', [
            'has_company' => !is_null($user->company),
            'company_id' => $user->company?->id,
            'company_name' => $user->company?->legal_name
        ]);

        $auditHistory = $user->company ?
            BankDetailsAudit::where('company_id', $user->company->id)
                ->with('changedBy')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get() :
            collect();

        \Log::info('Audit history count:', ['count' => $auditHistory->count()]);

        return view('profile.edit', compact('user', 'auditHistory'));
    }

    public function updateBankDetails(UpdateCompanyBankDetailsRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->company) {
            return back()->withErrors(['error' => 'Компания не найдена']);
        }

        DB::transaction(function () use ($user, $request) {
            $company = $user->company;
            $oldValues = $company->only([
                'bank_name', 'bank_account', 'bik', 'correspondent_account'
            ]);

            $company->update($request->validated());

            // Запись в аудит
            BankDetailsAudit::create([
                'company_id' => $company->id,
                'changed_by' => $user->id,
                'old_values' => $oldValues,
                'new_values' => $request->validated(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Отложенная синхронизация с 1С
            \App\Jobs\SyncCompanyWith1C::dispatch($company)->delay(now()->addMinutes(1));
        });

        return redirect()->route('profile.edit')
            ->with('success', 'Банковские реквизиты успешно обновлены');
    }

    public function exportToPdf(Request $request)
    {
        $user = $request->user();

        if (!$user->company) {
            return back()->withErrors(['error' => 'Компания не найдена']);
        }

        // Используем новый шаблон только с реквизитами
        $pdf = \PDF::loadView('profile.pdf.requisites', [
            'company' => $user->company,
            'user' => $user
        ]);

        // Устанавливаем имя файла
        $filename = 'реквизиты_' . Str::slug($user->company->legal_name) . '.pdf';

        return $pdf->download($filename);
    }
}

