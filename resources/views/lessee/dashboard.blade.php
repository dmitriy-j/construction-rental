@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Личный кабинет арендатора</h1>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Активные заказы</h5>
                    <p class="card-text display-4">{{ $stats['active_orders'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Ожидающие заказы</h5>
                    <p class="card-text display-4">{{ $stats['pending_orders'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Завершенные заказы</h5>
                    <p class="card-text display-4">{{ $stats['completed_orders'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Общая сумма</h5>
                    <p class="card-text display-4">{{ number_format($stats['total_spent'], 0) }} ₽</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Последние заказы</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Арендодатель</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->lessorCompany->legal_name }}</td>
                        <td>{{ number_format($order->total_amount, 0) }} ₽</td>
                        <td>
                            <span class="badge bg-{{ $order->status_color }}">
                                {{ $order->status_text }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('d.m.Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    @if($upcomingReturns->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">Ближайшие возвраты</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Оборудование</th>
                        <th>Арендодатель</th>
                        <th>Дата возврата</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upcomingReturns as $order)
                    <tr>
                        <td>
                            @foreach($order->items as $item)
                                {{ $item->equipment->title }}<br>
                            @endforeach
                        </td>
                        <td>{{ $order->lessorCompany->legal_name }}</td>
                        <td>{{ $order->end_date->format('d.m.Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $order->status_color }}">
                                {{ $order->status_text }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection