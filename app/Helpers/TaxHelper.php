<?php

// app/Helpers/TaxHelper.php или в существующий сервис

namespace App\Helpers;

use App\Models\Company;

class TaxHelper
{
    public static function getPlatformVatRate(): float
    {
        $platform = Company::where('is_platform', true)->first();

        if (! $platform) {
            \Log::error('Platform company not found in database');

            return 0.0;
        }

        return $platform->tax_system === 'vat' ? 22.0 : 0.0;
    }

    public static function calculateVatAmount(float $amount, float $vatRate): float
    {
        if ($vatRate <= 0) {
            return 0.0;
        }

        return round($amount * $vatRate / (100 + $vatRate), 2);
    }
}
