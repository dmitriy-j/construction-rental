@extends('layouts.app')
@php
    use App\Models\DeliveryNote;
    use App\Models\Waybill;
    use App\Models\Contract;
    use App\Models\CompletionAct;
@endphp

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">
            <i class="fas fa-file-alt text-primary me-2"></i>Документы
        </h1>

        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link {{ $type === 'delivery_notes' ? 'active' : '' }}"
                   href="{{ route('lessor.documents.index', ['type' => 'delivery_notes']) }}">
                    Транспортные накладные
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $type === 'waybills' ? 'active' : '' }}"
                   href="{{ route('lessor.documents.index', ['type' => 'waybills']) }}">
                    Путевые листы
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $type === 'contracts' ? 'active' : '' }}"
                   href="{{ route('lessor.documents.index', ['type' => 'contracts']) }}">
                    Договоры
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $type === 'completion_acts' ? 'active' : '' }}"
                   href="{{ route('lessor.documents.index', ['type' => 'completion_acts']) }}">
                    Акты выполненных работ
                </a>
            </li>
        </ul>
    </div>

    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        @if($type === 'delivery_notes')
            Отображаются транспортные накладные по вашей технике
        @elseif($type === 'waybills')
            Отображаются путевые листы по вашим заказам
        @elseif($type === 'contracts')
            Отображаются договоры с арендаторами
        @elseif($type === 'completion_acts')
            Отображаются акты выполненных работ
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @if($type === 'delivery_notes')
                            <th class="py-3">№ документа</th>
                            <th class="py-3">Дата создания</th>
                            <th class="py-3">Заказ</th>
                            <th class="py-3">Получатель</th>
                            <th class="py-3">Статус</th>
                            <th class="py-3 text-end">Действия</th>
                        @elseif($type === 'waybills')
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
    @forelse($documents as $doc)
    <tr>
        <td>#{{ $doc->id }}</td>
        <td>
            <div class="d-flex align-items-center">
                @if($doc->equipment?->mainImage)
                    <img src="{{ $doc->equipment->mainImage->url }}"
                         alt="{{ $doc->equipment->title }}"
                         class="rounded me-3"
                         style="width: 50px; height: 50px; object-fit: cover">
                @endif
                <div>
                    <div>{{ $doc->equipment->title ?? 'Удаленное оборудование' }}</div>
                    <div class="text-muted small">
                        {{ $doc->equipment->brand ?? '' }} {{ $doc->equipment->model ?? '' }}
                    </div>
                </div>
            </div>
        </td>
        <td>
            @if($doc->operator)
                <div>{{ $doc->operator->full_name }}</div>
                <div class="text-muted small">
                    {{ $doc->operator->license_number }}
                </div>
            @else
                <span class="text-danger">Не назначен</span>
            @endif
        </td>
        <td>
            @if($doc->start_date)
                <div class="fw-medium">
                    {{ $doc->start_date->format('d.m.Y') }} - {{ $doc->end_date->format('d.m.Y') }}
                </div>
                <div class="text-muted small">
                    {{ $doc->created_at->format('d.m.Y H:i') }}
                </div>
            @else
                <span class="text-danger">Дата не указана</span>
            @endif
        </td>
        <td>
            @if($doc->shift_type === 'day')
                <span class="badge bg-info py-2 px-3 rounded-pill">Дневная</span>
            @else
                <span class="badge bg-dark py-2 px-3 rounded-pill">Ночная</span>
            @endif
        </td>
        <td>
            <span class="badge bg-{{ $doc->status_color }} py-2 px-3 rounded-pill">
                {{ $doc->status_text }}
            </span>
        </td>
        <td class="text-end">
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('lessor.waybills.show', $doc) }}"
                   class="btn btn-sm btn-outline-primary"
                   title="Просмотреть">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('lessor.waybills.download', $doc) }}"
                   class="btn btn-sm btn-outline-secondary"
                   title="Скачать PDF">
                    <i class="fas fa-file-pdf"></i>
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
                        @elseif($type === 'contracts')
                            <th class="py-3">№ договора</th>
                            <th class="py-3">Дата заключения</th>
                            <th class="py-3">Арендатор</th>
                            <th class="py-3">Статус</th>
                            <th class="py-3 text-end">Действия</th>
                        @elseif($type === 'completion_acts')
                            <th class="py-3">№ акта</th>
                            <th class="py-3">Дата подписания</th>
                            <th class="py-3">Заказ</th>
                            <th class="py-3">Арендатор</th>
                            <th class="py-3 text-end">Действия</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        @if($type === 'delivery_notes')
                        <tr>
                            <td>{{ $doc->document_number ?? 'Черновик #'.$doc->id }}</td>
                            <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <a href="{{ route('lessor.orders.show', $doc->order_id) }}"
                                   class="text-primary fw-bold text-decoration-none">
                                    Заказ #{{ $doc->order_id }}
                                </a>
                            </td>
                            <td>{{ $doc->receiverCompany->legal_name ?? 'Платформа' }}</td>
                            <td>
                                <span class="badge
                                    @if($doc->status === DeliveryNote::STATUS_DRAFT) bg-warning
                                    @elseif($doc->status === DeliveryNote::STATUS_IN_TRANSIT) bg-info
                                    @elseif($doc->status === DeliveryNote::STATUS_DELIVERED) bg-success
                                    @else bg-secondary @endif py-2 px-3 rounded-pill">
                                    {{ DeliveryNote::statuses()[$doc->status] ?? $doc->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    @if($doc->status === DeliveryNote::STATUS_DRAFT)
                                        <a href="{{ route('lessor.delivery-notes.edit', $doc) }}"
                                           class="btn btn-sm btn-warning" title="Заполнить данные">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    @if($doc->status !== DeliveryNote::STATUS_DRAFT)
                                        {{--<div class="btn-group">
                                            <a href="{{ route('delivery-notes.export.excel', $doc) }}"
                                            class="btn btn-sm btn-success" title="Excel">
                                                <i class="fas fa-file-excel"></i>
                                            </a>
                                            <a href="{{ route('delivery-notes.export.pdf', $doc) }}"
                                            class="btn btn-sm btn-danger" title="PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        </div>--}}
                                    @endif

                                    <a href="{{ route('lessor.orders.show', $doc->order_id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Перейти к заказу">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @elseif($type === 'waybills')
                        <tr>
                            <td>#{{ $doc->id }}</td>
                            <td>
                                {{ $doc->equipment->title ?? 'Удаленное оборудование' }}
                                @if($doc->equipment)
                                    <div class="text-muted small">
                                        {{ $doc->equipment->brand }} {{ $doc->equipment->model }}
                                    </div>
                                @endif
                            </td>

                            {{-- Безопасная обработка даты --}}
                            <td>
                                @if($doc->start_date)
                                    {{ $doc->start_date->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">Нет данных</span>
                                @endif
                            </td>

                            <td>
                                @if($doc->end_date)
                                    {{ $doc->end_date->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">Нет данных</span>
                                @endif
                            </td>

                            <td>
                                @if($doc->shift)
                                    @if($doc->shift === 'day')
                                        <span class="badge bg-info py-2 px-3 rounded-pill">Дневная</span>
                                    @else
                                        <span class="badge bg-dark py-2 px-3 rounded-pill">Ночная</span>
                                    @endif
                                @else
                                    <span class="text-muted">Не указана</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('lessor.orders.show', $doc->order_id) }}"
                                class="text-primary fw-bold text-decoration-none">
                                    Заказ #{{ $doc->order_id }}
                                </a>
                            </td>

                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('lessor.waybills.show', $doc) }}"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Просмотреть">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('lessor.waybills.download', $doc) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Скачать PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @elseif($type === 'contracts')
                        <tr>
                            <td>{{ $doc->contract_number }}</td>
                            <td>{{ $doc->created_at->format('d.m.Y') }}</td>
                            <td>{{ $doc->lesseeCompany->legal_name ?? 'Нет данных' }}</td>
                            <td>
                                <span class="badge bg-{{ $doc->is_active ? 'success' : 'secondary' }} py-2 px-3 rounded-pill">
                                    {{ $doc->is_active ? 'Активен' : 'Завершен' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('documents.download', ['id' => $doc->id, 'type' => 'contracts']) }}"
                                   class="btn btn-sm btn-outline-primary">
                                   <i class="fas fa-download"></i> Скачать
                                </a>
                            </td>
                        </tr>
                        @elseif($type === 'completion_acts')
                        <tr>
                            <td>АВР-{{ $doc->id }}</td>
                            <td>{{ $doc->signed_at?->format('d.m.Y') ?? 'Не подписан' }}</td>
                            <td>
                                <a href="{{ route('lessor.orders.show', $doc->order_id) }}"
                                class="text-primary fw-bold text-decoration-none">
                                    Заказ #{{ $doc->order_id }}
                                </a>
                            </td>
                            <td>{{ $doc->order->lesseeCompany->legal_name ?? 'Нет данных' }}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <!-- Кнопка просмотра -->
                                    <a href="{{ route('lessor.documents.completion_acts.show', $doc) }}"
                                    class="btn btn-sm btn-info"
                                    title="Просмотреть акт">
                                    <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- Кнопка скачивания -->
                                    <a href="{{ route('lessor.documents.download', ['id' => $doc->id, 'type' => 'completion_acts']) }}"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Скачать PDF">
                                    <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="{{ $type === 'completion_acts' ? 5 : 7 }}" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                <p class="h5">
                                    @if($type === 'delivery_notes')
                                        Транспортные накладные отсутствуют
                                    @elseif($type === 'waybills')
                                        Путевые листы отсутствуют
                                    @elseif($type === 'contracts')
                                        Договоры отсутствуют
                                    @else
                                        Акты выполненных работ отсутствуют
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
        <div class="card-footer bg-white border-0">
            <div class="d-flex justify-content-center">
                {{ $documents->appends(['type' => request('type')])->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-bottom: none;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
        padding: 0.75rem 1.25rem;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Инициализация всплывающих подсказок
    $('[data-toggle="tooltip"]').tooltip();

    // Автоматическое обновление статуса документов
    @if(in_array($type, ['delivery_notes', 'waybills']))
    setInterval(function() {
        $.ajax({
            url: "{{ route('lessor.documents.status-update') }}",
            type: "GET",
            data: { type: "{{ $type }}" },
            success: function(data) {
                // Обновляем только статусы документов
                data.forEach(function(doc) {
                    const badge = $(`#status-badge-${doc.id}`);
                    if (badge.length) {
                        badge.removeClass('bg-warning bg-info bg-success bg-secondary')
                              .addClass('bg-' + doc.status_color)
                              .text(doc.status_text);
                    }
                });
            }
        });
    }, 30000); // Каждые 30 секунд
    @endif
});
</script>
@endpush
