@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Мои заказы</h1>

        <div class="col-md-3">
            <select class="form-select" onchange="window.location.href = this.value">
                <option value="{{ route('lessee.orders.index') }}">Все статусы</option>
                @foreach([
                    App\Models\Order::STATUS_PENDING,
                    App\Models\Order::STATUS_PENDING_APPROVAL,
                    App\Models\Order::STATUS_CONFIRMED,
                    App\Models\Order::STATUS_ACTIVE,
                    App\Models\Order::STATUS_COMPLETED,
                    App\Models\Order::STATUS_CANCELLED,
                    App\Models\Order::STATUS_EXTENSION_REQUESTED,
                    App\Models\Order::STATUS_REJECTED
                ] as $status)
                <option value="{{ route('lessee.orders.index', ['status' => $status]) }}"
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
                            <td>
                                @if($order->lessorCompany)
                                    {{ $order->lessorCompany->legal_name }}
                                @else
                                    Компания не указана
                                @endif
                            </td>
                            <td>{{ number_format($order->total_amount, 2) }} ₽</td>
                            <td>
                                <span class="badge bg-{{ $order->status_color }}">
                                    {{ $order->status_text }}
                                    @if($order->status === App\Models\Order::STATUS_REJECTED && $order->rejection_reason)
                                        <i class="fas fa-info-circle ms-1"
                                           title="Причина: {{ $order->rejection_reason }}"
                                           data-bs-toggle="tooltip"></i>
                                    @endif
                                </span>
                            </td>
                            <td>{{ $order->created_at->format('d.m.Y') }}</td>
                            <td>
                                <a href="{{ route('lessee.orders.show', $order) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if(in_array($order->status, [
                                    App\Models\Order::STATUS_PENDING,
                                    App\Models\Order::STATUS_PENDING_APPROVAL,
                                    App\Models\Order::STATUS_CONFIRMED
                                ]))
                                    <form action="{{ route('lessee.orders.cancel', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Отменить заказ">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($order->status === App\Models\Order::STATUS_ACTIVE)
                                    <button class="btn btn-sm btn-warning request-extension-btn"
                                            data-order-id="{{ $order->id }}"
                                            data-order-end-date="{{ $order->end_date->format('Y-m-d') }}"
                                            title="Запросить продление">
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
        </div>
        @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обработка кликов по кнопкам продления
        document.querySelectorAll('.request-extension-btn').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const endDate = this.getAttribute('data-order-end-date');

                // Рассчитываем минимальную дату (следующий день после текущей endDate)
                const minDate = new Date(endDate);
                minDate.setDate(minDate.getDate() + 1);
                const minDateStr = minDate.toISOString().split('T')[0];

                // Создаем форму для ввода даты
                Swal.fire({
                    title: `Запрос продления заказа #${orderId}`,
                    html: `
                        <form id="extensionForm" method="POST" action="/lessee/orders/${orderId}/request-extension">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Новая дата окончания</label>
                                <input type="date" name="new_end_date" class="form-control"
                                       min="${minDateStr}" required>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Отправить запрос',
                    cancelButtonText: 'Отмена',
                    focusConfirm: false,
                    preConfirm: () => {
                        const form = document.getElementById('extensionForm');
                        const dateInput = form.querySelector('input[name="new_end_date"]');

                        if (!dateInput.value) {
                            Swal.showValidationMessage('Пожалуйста, выберите дату');
                            return false;
                        }

                        // Возвращаем данные формы
                        return new FormData(form);
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Отправляем форму через AJAX
                        fetch(`/lessee/orders/${orderId}/request-extension`, {
                            method: 'POST',
                            body: result.value,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Запрос отправлен!',
                                    text: data.message,
                                    willClose: () => {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Ошибка',
                                    text: data.message
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Ошибка',
                                text: 'Произошла ошибка при отправке запроса'
                            });
                        });
                    }
                });
            });
        });

        // Инициализация тултипов
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush

@endsection
