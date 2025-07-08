@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Заказы на мою технику</h1>
        
        <div class="col-md-3">
            <select class="form-select" onchange="window.location.href = this.value">
                <option value="{{ route('lessor.orders') }}">Все статусы</option>
                @foreach(\App\Models\Order::statuses() as $status)
                <option value="{{ route('lessor.orders', ['status' => $status]) }}"
                    {{ request('status') == $status ? 'selected' : '' }}>
                    {{ \App\Models\Order::statusText($status) }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Арендатор</th>
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
                            <td>{{ $order->lesseeCompany->legal_name }}</td>
                            <td>{{ number_format($order->total_amount, 2) }} ₽</td>
                            <td>
                                <span class="badge bg-{{ $order->status_color }}">
                                    {{ $order->status_text }}
                                </span>
                            </td>
                            <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                            <td class="d-flex gap-2">
                                <a href="{{ route('lessor.orders.show', $order) }}" 
                                   class="btn btn-sm btn-outline-primary" title="Просмотр">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if($order->status === \App\Models\Order::STATUS_PENDING)
                                <form action="{{ route('lessor.orders.updateStatus', $order) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="confirmed">
                                    <button class="btn btn-sm btn-success" title="Подтвердить">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>

                                <form action="{{ route('lessor.orders.updateStatus', $order) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="btn btn-sm btn-danger" title="Отменить">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                @endif

                                @if($order->status === \App\Models\Order::STATUS_CONFIRMED)
                                <form action="{{ route('lessor.orders.markActive', $order) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-info" title="Начать аренду">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </form>
                                @endif

                                @if($order->status === \App\Models\Order::STATUS_ACTIVE)
                                <form action="{{ route('lessor.orders.markCompleted', $order) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-success" title="Завершить аренду">
                                        <i class="fas fa-flag-checkered"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">Заказы не найдены</td>
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
</div>

@foreach($orders as $order)
@if($order->status === \App\Models\Order::STATUS_EXTENSION_REQUESTED)
<div class="modal fade" id="extensionModal-{{ $order->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('lessor.orders.handleExtension', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Обработка продления заказа #{{ $order->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Арендатор запросил продление до: {{ $order->requested_end_date->format('d.m.Y') }}</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Корректировка цены (₽)</label>
                        <input type="number" name="price_adjustment" class="form-control" min="0" step="0.01">
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="action" id="approveAction" value="approve" checked>
                        <label class="form-check-label" for="approveAction">
                            Одобрить продление
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="action" id="rejectAction" value="reject">
                        <label class="form-check-label" for="rejectAction">
                            Отклонить запрос
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Подтвердить</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection