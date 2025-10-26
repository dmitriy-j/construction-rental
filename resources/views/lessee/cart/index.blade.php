@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Корзина</h1>

    <!-- Навигация между типами корзины -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="cartTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="regular-tab" data-bs-toggle="tab"
                            data-bs-target="#regular-cart" type="button" role="tab">
                        <i class="bi bi-cart me-2"></i>Обычная корзина
                        <span class="badge bg-primary ms-2" id="regular-count">
                            {{ $cart->items->count() ?? 0 }}
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="proposal-tab" data-bs-toggle="tab"
                            data-bs-target="#proposal-cart" type="button" role="tab">
                        <i class="bi bi-handshake me-2"></i>Подтвержденные предложения
                        <span class="badge bg-success ms-2" id="proposal-count">
                            0
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content" id="cartTabsContent">
        <!-- Обычная корзина -->
        <div class="tab-pane fade show active" id="regular-cart" role="tabpanel">
            @include('lessee.cart.partials.regular-cart')
        </div>

        <!-- Корзина подтвержденных предложений -->
        <div class="tab-pane fade" id="proposal-cart" role="tabpanel">
            @include('lessee.cart.partials.proposal-cart')
        </div>
    </div>
</div>
@endsection
