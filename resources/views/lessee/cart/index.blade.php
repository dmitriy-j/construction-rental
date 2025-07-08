@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Корзина</h1>
    
    @if($cart->items->isEmpty())
        <div class="alert alert-info">Ваша корзина пуста</div>
    @else
        <form action="{{ route('lessee.cart.updateDates') }}" method="POST">
            @csrf
            <div class="row mb-4">
                <div class="col-md-4">
                    <label>Дата начала аренды</label>
                    <input type="date" name="start_date" 
                           value="{{ $cart->start_date->format('Y-m-d') }}" 
                           class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Дата окончания</label>
                    <input type="date" name="end_date" 
                           value="{{ $cart->end_date->format('Y-m-d') }}" 
                           class="form-control">
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-primary">Обновить даты</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Оборудование</th>
                            <th>Период</th>
                            <th>Кол-во</th>
                            <th>Цена/ед.</th>
                            <th>Сбор</th>
                            <th>Итого</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cart->items as $item)
                        <tr>
                            <td>{{ $item->rentalTerm->equipment->title }}</td>
                            <td>{{ $item->rentalTerm->full_period }}</td>
                            <td>{{ $item->period_count }}</td>
                            <td>{{ number_format($item->base_price, 2) }} ₽</td>
                            <td>{{ number_format($item->platform_fee, 2) }} ₽</td>
                            <td>{{ number_format($item->total, 2) }} ₽</td>
                            <td>
                                <form action="{{ route('lessee.cart.remove', $item->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">×</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <div class="h4">Общая сумма: {{ number_format($total, 2) }} ₽</div>
                <form action="{{ route('lessee.checkout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">Оформить заказ</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection