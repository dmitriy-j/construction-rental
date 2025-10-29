<?php
// app/Services/CompanyBankDetailsService.php

namespace App\Services;

use App\Models\Company;
use App\Models\BankDetailsAudit;
use Illuminate\Support\Facades\DB;

class CompanyBankDetailsService
{
    public function updateBankDetails(Company $company, array $validatedData, $changedBy, $request): void
    {
        DB::transaction(function () use ($company, $validatedData, $changedBy, $request) {
            $oldValues = $company->only([
                'bank_name', 'bank_account', 'bik', 'correspondent_account'
            ]);

            $company->update($validatedData);

            BankDetailsAudit::create([
                'company_id' => $company->id,
                'changed_by' => $changedBy->id,
                'old_values' => $oldValues,
                'new_values' => $validatedData,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });
    }

    public function getAuditHistory(Company $company, int $limit = 10)
    {
        return BankDetailsAudit::where('company_id', $company->id)
            ->with('changedBy')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
