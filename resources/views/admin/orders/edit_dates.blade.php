{{-- resources/views/admin/orders/edit_dates.blade.php --}}
@extends('layouts.app')

@section('title', 'Изменение дат заказа #' . $order->company_order_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Изменение дат заказа #{{ $order->company_order_number }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-default">
                            <i class="fas fa-arrow-left"></i> Назад к заказу
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('availability_check'))
                        @php $availability = session('availability_check'); @endphp
                        <div class="alert alert-warning">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Внимание!</h5>
                            <p>Следующее оборудование недоступно на выбранные даты:</p>
                            <ul>
                                @foreach($availability['unavailable_equipment'] as $equipment)
                                    <li><strong>{{ $equipment['equipment'] }}</strong></li>
                                @endforeach
                            </ul>
                            <p class="mb-0">
                                <form action="{{ route('admin.orders.force-update-dates', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="start_date" value="{{ old('start_date') }}">
                                    <input type="hidden" name="end_date" value="{{ old('end_date') }}">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-exclamation-triangle"></i> Принудительно изменить даты
                                    </button>
                                </form>
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('admin.orders.update-dates', $order) }}" method="POST" id="datesForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Дата начала аренды *</label>
                                    <input type="date"
                                           class="form-control @error('start_date') is-invalid @enderror"
                                           id="start_date"
                                           name="start_date"
                                           value="{{ old('start_date', $order->start_date->format('Y-m-d')) }}"
                                           required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">Дата окончания аренды *</label>
                                    <input type="date"
                                           class="form-control @error('end_date') is-invalid @enderror"
                                           id="end_date"
                                           name="end_date"
                                           value="{{ old('end_date', $order->end_date->format('Y-m-d')) }}"
                                           required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="icon fas fa-info-circle"></i> Информация о пересчете</h6>
                                    <ul class="mb-0">
                                        <li>Будут пересчитаны рабочие часы (period_count) для каждой позиции</li>
                                        <li>Суммы аренды будут пересчитаны на основе новых дат</li>
                                        <li>Наценка платформы будет применена заново</li>
                                        <li>Система проверит доступность оборудования на новые даты</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        @if(session('availability_check'))
                            <input type="hidden" name="confirm_availability" value="1">
                        @endif

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calculator"></i> Пересчитать заказ
                            </button>

                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-default">
                                Отмена
                            </a>
                        </div>
                    </form>

                    {{-- Текущая информация о заказе --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Текущие данные заказа</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <tr>
                                        <th>Период аренды:</th>
                                        <td>{{ $order->start_date->format('d.m.Y') }} - {{ $order->end_date->format('d.m.Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Дней аренды:</th>
                                        <td>{{ $order->start_date->diffInDays($order->end_date) + 1 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Общая сумма:</th>
                                        <td>{{ number_format($order->total_amount, 2) }} ₽</td>
                                    </tr>
                                    <tr>
                                        <th>Наценка платформы:</th>
                                        <td>{{ number_format($order->platform_fee, 2) }} ₽</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Проверка доступности при изменении дат
    function checkAvailability() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (startDate && endDate) {
            $.ajax({
                url: '{{ route("admin.orders.check-dates-availability", $order) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    // Можно добавить визуальную индикацию доступности
                    console.log('Availability check:', response);
                }
            });
        }
    }

    $('#start_date, #end_date').change(checkAvailability);
});
</script>
@endsection
