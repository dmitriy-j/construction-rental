<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index()
    {
        $company = Auth::user()->company;
        $balance = $this->balanceService->getCurrentBalance($company);
        $transactions = $this->balanceService->getTransactionHistory($company, 20);

        return view('lessee.balance.index', compact('balance', 'transactions'));
    }
}
