<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\BalanceService;

class CheckCreditLimit
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function handle(Request $request, Closure $next)
    {
        $company = auth()->user()->company;

        if ($company->is_lessee && $company->credit_limit > 0) {
            $balance = $this->balanceService->getCurrentBalance($company);

            if ($balance < -$company->credit_limit) {
                return redirect()->back()
                    ->with('error', 'Превышен кредитный лимит. Невозможно создать новый заказ.');
            }
        }

        return $next($request);
    }
}
