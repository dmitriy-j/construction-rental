@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                @if(request('order') == 'all')
                    Все путевые листы
                @else
                    Путевые листы для заказа #{{ $order->id }}
                @endif
            </h1>
            @if(request('order') != 'all')
            <div class="text-muted small">
                {{ $order->created_at->format('d.m.Y') }} -
                Статус: {{ $order->status_text }}
            </div>
            @endif
        </div>
        <div>
            <a href="{{ route('lessor.documents.index', ['type' => 'waybills']) }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Назад к списку
            </a>
            @if(request('order') != 'all')
            <a href="{{ route('lessor.orders.show', $order) }}"
               class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt me-2"></i> К заказу
            </a>
            @endif
        </div>
    </div>

    {{-- Фильтры и сортировка --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0">Фильтры и сортировка</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Статус</label>
                    <select class="form-select" id="status-filter">
                        <option value="">Все статусы</option>
                        <option value="future">Будущие</option>
                        <option value="active">Активные</option>
                        <option value="completed">Завершённые</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Тип смены</label>
                    <select class="form-select" id="shift-type-filter">
                        <option value="">Все типы</option>
                        <option value="day">Дневные</option>
                        <option value="night">Ночные</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Сортировка</label>
                    <select class="form-select" id="sort-order">
                        <option value="newest">Сначала новые</option>
                        <option value="oldest">Сначала старые</option>
                        <option value="period">По периоду смен</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="apply-filters">
                        Применить
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Таблица путевых листов --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">ID</th>
                        <th class="py-3">Оборудование</th>
                        <th class="py-3">Оператор</th>
                        <th class="py-3">Период смен</th>
                        <th class="py-3">Тип смены</th>
                        <th class="py-3">Статус</th>
                        <th class="py-3 text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waybills as $waybill)
                    <tr>
                        <td>#{{ $waybill->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($waybill->equipment?->mainImage)
                                    <img src="{{ $waybill->equipment->mainImage->url }}"
                                         alt="{{ $waybill->equipment->title }}"
                                         class="rounded me-3"
                                         style="width: 50px; height: 50px; object-fit: cover">
                                @endif
                                <div>
                                    <div>{{ $waybill->equipment->title ?? 'Удаленное оборудование' }}</div>
                                    <div class="text-muted small">
                                        {{ $waybill->equipment->brand ?? '' }} {{ $waybill->equipment->model ?? '' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($waybill->operator)
                                <div>{{ $waybill->operator->full_name }}</div>
                                <div class="text-muted small">
                                    {{ $waybill->operator->license_number }}
                                </div>
                            @else
                                <span class="text-danger">Не назначен</span>
                            @endif
                        </td>
                        <td>
                            @if($waybill->start_date)
                                <div class="fw-medium">
                                    {{ $waybill->start_date->format('d.m.Y') }} - {{ $waybill->end_date->format('d.m.Y') }}
                                </div>
                                <div class="text-muted small">
                                    {{ $waybill->created_at->format('d.m.Y H:i') }}
                                </div>
                            @else
                                <span class="text-danger">Дата не указана</span>
                            @endif
                        </td>
                        <td>
                            @if($waybill->shift_type === 'day')
                                <span class="badge bg-info py-2 px-3 rounded-pill">Дневная</span>
                            @else
                                <span class="badge bg-dark py-2 px-3 rounded-pill">Ночная</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $waybill->status_color }} py-2 px-3 rounded-pill">
                                {{ $waybill->status_text }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('lessor.waybills.show', $waybill) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Просмотреть детали">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                <p class="h5">Путевые листы отсутствуют</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($waybills->hasPages())
        <div class="card-footer bg-white border-0">
            <div class="d-flex justify-content-center">
                {{ $waybills->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Применение фильтров
    document.getElementById('apply-filters').addEventListener('click', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('status', document.getElementById('status-filter').value);
        params.set('shift_type', document.getElementById('shift-type-filter').value);
        params.set('sort', document.getElementById('sort-order').value);

        window.location.search = params.toString();
    });

    // Установка текущих значений фильтров
    const urlParams = new URLSearchParams(window.location.search);
    document.getElementById('status-filter').value = urlParams.get('status') || '';
    document.getElementById('shift-type-filter').value = urlParams.get('shift_type') || '';
    document.getElementById('sort-order').value = urlParams.get('sort') || 'newest';
});
</script>
@endpush
