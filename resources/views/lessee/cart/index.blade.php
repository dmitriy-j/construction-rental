@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1>Корзина</h1>

    @if($cart->items->isEmpty())
        <div class="alert alert-info">Ваша корзина пуста</div>
    @else
        <form action="{{ route('cart.update-dates') }}" method="POST">
            @csrf
            <div class="row mb-4">
                <div class="col-md-4">
                    <label>Дата начала аренды</label>
                    <input type="date" name="start_date"
                           value="{{ $cart->start_date ? $cart->start_date->format('Y-m-d') : now()->format('Y-m-d') }}"
                           class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Дата окончания</label>
                    <input type="date" name="end_date"
                           value="{{ $cart->end_date ? $cart->end_date->format('Y-m-d') : now()->addDays(1)->format('Y-m-d') }}"
                           class="form-control" required>
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-primary">Обновить даты</button>
                </div>
            </div>
        </form>
        <div class="alert alert-info">
            Рассчитанная стоимость является ориентировочной. Окончательная сумма
            будет определена в акте выполненных работ на основании фактического
            времени использования техники.
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Оборудование</th>
                            <th>Период аренды</th>
                            <th>Даты аренды</th>
                            <th>Кол-во периодов</th>
                            <th>Цена за период</th>
                            <th>Комиссия платформы</th>
                            <th>Доставка</th>
                            <th>Итого</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cart->items as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('catalog.show', $item->rentalTerm->equipment) }}">
                                        {{ $item->rentalTerm->equipment->title }}
                                    </a>
                                </td>
                                <td>{{ $item->rentalTerm->period }}</td>
                                <td>
                                    @if($item->start_date && $item->end_date)
                                        {{ $item->start_date->format('d.m.Y') }} -
                                        {{ $item->end_date->format('d.m.Y') }}
                                    @else
                                        <span class="text-danger">Даты не указаны</span>
                                    @endif
                                </td>
                                <td>{{ $item->period_count }}</td>
                                <td>{{ number_format($item->base_price, 2) }} ₽</td>
                                <td>{{ number_format($item->platform_fee, 2) }} ₽</td>
                                <td>
                                    @if($item->delivery_cost > 0)
                                        {{ number_format($item->delivery_cost, 2) }} ₽
                                        <br><small>
                                            От: {{ $item->deliveryFrom->short_address ?? 'N/A' }}
                                            <br>До: {{ $item->deliveryTo->short_address ?? 'N/A' }}
                                        </small>
                                    @else
                                        Самовывоз
                                    @endif
                                </td>
                                <td>{{ number_format($item->base_price * $item->period_count, 2) }} ₽</td>
                                <td>
                                    <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Удалить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6"></td>
                            <td><strong>Итого:</strong></td>
                            <td colspan="2">
                                <strong>{{ number_format($total, 2) }} ₽</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('catalog.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Продолжить выбор
            </a>
            <form action="{{ route('checkout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle"></i> Оформить заказ
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
