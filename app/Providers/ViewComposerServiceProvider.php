<?php

namespace App\Providers;

use Illuminate\Support\Facades\View; // Добавьте этот импорт
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

   public function boot()
    {
        View::composer('partials.sidebar', function ($view) {
            $cartCount = 0;

            try {
                if (auth()->check()) {
                    $cart = auth()->user()->cart;
                    $cartCount = $cart ? $cart->items()->count() : 0;
                }
            } catch (\Exception $e) {
                // Логирование ошибки при необходимости
                $cartCount = 0;
            }

            $view->with('cartCount', $cartCount);
        });
    }
}
