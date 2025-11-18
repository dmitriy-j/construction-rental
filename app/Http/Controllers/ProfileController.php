<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateCompanyBankDetailsRequest;
use App\Http\Requests\UpdateCompanyLegalDetailsRequest; // 🔥 ДОБАВЬТЕ ЭТОТ ИМПОРТ
use App\Models\BankDetailsAudit;
use App\Models\CompanyDetailsAudit; // 🔥 ДОБАВЬТЕ ЭТОТ ИМПОРТ
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * Обновление юридических реквизитов компании
     */
    public function updateCompanyLegalDetails(UpdateCompanyLegalDetailsRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->company) {
            return back()->withErrors(['error' => 'Компания не найдена']);
        }

        DB::transaction(function () use ($user, $request) {
            $company = $user->company;
            $validated = $request->validated();

            // Определяем фактический адрес
            $actualAddress = $request->boolean('same_as_legal')
                ? $validated['legal_address']
                : $validated['actual_address'];

            // Обрабатываем KPP в зависимости от типа организации
            $kpp = null;
            if ($validated['legal_type'] === 'ooo') {
                $kpp = $validated['kpp'] ?? null;
            }

            $oldValues = $company->only([
                'legal_type', 'legal_name', 'inn', 'kpp', 'ogrn', 'okpo',
                'tax_system', 'legal_address', 'actual_address', 'director_name', 'contacts'
            ]);

            $company->update([
                'legal_type' => $validated['legal_type'],
                'legal_name' => $validated['legal_name'],
                'tax_system' => $validated['tax_system'],
                'inn' => $validated['inn'],
                'kpp' => $kpp, // KPP только для ООО
                'ogrn' => $validated['ogrn'],
                'okpo' => $validated['okpo'] ?? null,
                'legal_address' => $validated['legal_address'],
                'actual_address' => $actualAddress,
                'director_name' => $validated['director_name'],
                'contacts' => $validated['contacts'] ?? null,
            ]);

            // Запись в аудит юридических реквизитов
            CompanyDetailsAudit::create([
                'company_id' => $company->id,
                'changed_by' => $user->id,
                'old_values' => $oldValues,
                'new_values' => $company->fresh()->only(array_keys($oldValues)),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Отложенная синхронизация с 1С
            \App\Jobs\SyncCompanyWith1C::dispatch($company)->delay(now()->addMinutes(1));
        });

        return redirect()->route('profile.edit')
            ->with('success', 'Юридические реквизиты компании успешно обновлены');
    }

    /**
     * Обновление банковских реквизитов компании
     */
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
