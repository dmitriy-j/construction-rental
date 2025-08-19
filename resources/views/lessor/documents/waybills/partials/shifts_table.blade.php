<div id="shifts-table">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Список смен</h5>
        <div class="text-muted small">
            Заполнено: {{ $filledShifts }}/{{ $totalShifts }}
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Дата смены</th>
                    <th>Часы работы</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($waybill->shifts as $shift)
                <tr class="{{ $shift->hours_worked == 0 ? 'table-warning' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $shift->shift_date->format('d.m.Y') }}
                        @if($waybill->shift_type === 'night')
                            <span class="badge bg-dark">Ночная</span>
                        @endif
                    </td>
                    <td>{{ $shift->hours_worked }} ч</td>
                    <td>
                        @if($shift->hours_worked > 0)
                            {{ number_format($shift->hours_worked * $shift->hourly_rate, 2) }} ₽
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($shift->hours_worked == 0)
                            <span class="badge bg-warning">Не заполнена</span>
                        @else
                            <span class="badge bg-success">Заполнена</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('lessor.waybills.show', $waybill) }}?shift_id={{ $shift->id }}"
                        class="btn btn-sm btn-outline-primary"
                        title="Редактировать">
                        <i class="fas fa-edit"></i>
                        </a>

                        {{-- Исправляем класс кнопки --}}
                        <button class="btn btn-sm btn-outline-danger delete-shift-btn"
                                data-id="{{ $shift->id }}"
                                title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($waybill->shifts->isEmpty())
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Нет добавленных смен
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small">
            Всего смен: {{ $waybill->shifts->count() }}, заполнено: {{ $waybill->shifts->whereNotNull('hours_worked')->count() }}
        </div>

        @if($waybill->shifts->count() >= 10)
            <div class="alert alert-warning mb-0 py-1 px-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Достигнут лимит в 10 смен
            </div>
        @endif
    </div>
</div>
