<?php

namespace App\Helpers;

use App\Models\Company;

class TaxHelper
{
    public static function getPlatformVatRate(): float
    {
        $platform = Company::where('is_platform', true)->first();

        if (!$platform) {
            \Log::error('Platform company not found in database');
            return 0.0;
        }

        // Для платформы на общей системе налогообложения возвращаем ставку из конфига
        return $platform->tax_system === 'vat' || $platform->tax_system === 'osn'
            ? (float) config('vat.rate', 22.0)
            : 0.0;
    }

    public static function getCompanyVatRate(?Company $company = null): float
    {
        // Если компания не передана, возвращаем ставку по умолчанию для компаний на общей системе
        if (!$company) {
            return (float) config('vat.rate', 22.0);
        }

        // Определяем ставку НДС в зависимости от системы налогообложения компании
        return match($company->tax_system ?? 'osn') {
            'usn', 'usn_income', 'patent', 'envd' => 0.0,
            default => (float) config('vat.rate', 22.0),
        };
    }

    public static function calculateVatAmount(float $amount, float $vatRate): float
    {
        if ($vatRate <= 0) {
            return 0.0;
        }

        return round($amount * $vatRate / (100 + $vatRate), 2);
    }

    public static function calculateAmountWithoutVat(float $amountWithVat, float $vatRate): float
    {
        if ($vatRate <= 0) {
            return $amountWithVat;
        }

        return round($amountWithVat * 100 / (100 + $vatRate), 2);
    }
}
