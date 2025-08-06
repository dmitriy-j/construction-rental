@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">
            <i class="fas fa-file-alt text-primary me-2"></i>Путевые листы для заказа #{{ $order->id }}
        </h1>
        <a href="{{ route('lessor.orders.show', $order) }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Назад к заказу
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">ID</th>
                        <th class="py-3">Оборудование</th>
                        <th class="py-3">Дата</th>
                        <th class="py-3">Смена</th>
                        <th class="py-3">Статус</th>
                        <th class="py-3 text-end">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waybills as $waybill)
                    <tr>
                        <td>#{{ $waybill->id }}</td>
                        <td>
                            {{ $waybill->equipment->title ?? 'Удаленное оборудование' }}
                            @if($waybill->equipment)
                                <div class="text-muted small">
                                    {{ $waybill->equipment->brand }} {{ $waybill->equipment->model }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $waybill->work_date->format('d.m.Y') }}</td>
                        <td>
                            @if($waybill->shift === \App\Models\Waybill::SHIFT_DAY)
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
                        <td colspan="6" class="text-center py-5">
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
    </div>
</div>
@endsection
