@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Мои заказы</h1>
        
        <div class="col-md-3">
            <select class="form-select" onchange="window.location.href = this.value">
                <option value="{{ route('lessee.orders') }}">Все статусы</option>
                @foreach(['pending', 'confirmed', 'active', 'completed', 'cancelled', 'extension_requested'] as $status)
                <option value="{{ route('lessee.orders', ['status' => $status]) }}" 
                    {{ request('status') == $status ? 'selected' : '' }}>
                    {{ \App\Models\Order::statusText($status) }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Арендодатель</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
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
                        <td>
                            <a href="{{ route('lessee.orders.show', $order) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            @if(in_array($order->status, [\App\Models\Order::STATUS_PENDING, \App\Models\Order::STATUS_CONFIRMED]))
                                <form action="{{ route('lessee.orders.cancel', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-danger" title="Отменить заказ">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            @endif
                            
                            @if($order->status === \App\Models\Order::STATUS_ACTIVE)
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                        data-bs-target="#extensionModal-{{ $order->id }}" title="Запросить продление">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Заказы не найдены</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>

@foreach($orders as $order)
@if($order->status === \App\Models\Order::STATUS_ACTIVE)
<div class="modal fade" id="extensionModal-{{ $order->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('lessee.orders.requestExtension', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Запрос продления заказа #{{ $order->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Новая дата окончания</label>
                        <input type="date" name="new_end_date" class="form-control" 
                               min="{{ \Carbon\Carbon::parse($order->end_date)->addDay()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Отправить запрос</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection