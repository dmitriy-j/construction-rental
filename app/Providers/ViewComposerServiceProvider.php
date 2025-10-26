<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('partials.sidebar', function ($view) {
            $newOrdersCount = 0;
            $user = Auth::user();

            if ($user && $user->company) {
                $cacheKey = 'new_orders_count_'.$user->company_id;

                $newOrdersCount = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user) {
                    return Order::where('lessor_company_id', $user->company_id)
                        ->where('status', Order::STATUS_PENDING_APPROVAL)
                        ->count();
                });
            }

            $view->with('newOrdersCount', $newOrdersCount);
        });

    }
}
