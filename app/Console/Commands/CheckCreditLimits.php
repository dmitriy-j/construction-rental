<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Notifications\CreditLimitAlertNotification;
use Illuminate\Console\Command;

class CheckCreditLimits extends Command
{
    protected $signature = 'finance:check-credit-limits';

    protected $description = 'Check companies credit limits and send notifications';

    public function handle()
    {
        $companies = Company::where('is_lessee', true)
            ->where('credit_limit', '>', 0)
            ->get();

        foreach ($companies as $company) {
            $balance = app(\App\Services\BalanceService::class)->getCurrentBalance($company);

            // Если баланс отрицательный и превышает 80% кредитного лимита
            if ($balance < 0 && abs($balance) >= ($company->credit_limit * 0.8)) {
                $company->notify(new CreditLimitAlertNotification($company, $balance));
                $this->info("Уведомление отправлено для компании: {$company->legal_name}");
            }
        }

        $this->info('Проверка кредитных лимитов завершена.');
    }
}
