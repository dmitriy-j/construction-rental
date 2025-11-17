@extends('layouts.app')
@php use App\Models\DeliveryNote; @endphp
@section('content')
<div class="container">
    <h1>Документы</h1>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'contracts' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'contracts']) }}">Договоры</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'waybills' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'waybills']) }}">Путевые листы</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'delivery_notes' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'delivery_notes']) }}">Накладные</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'completion_acts' ? 'active' : '' }}"
               href="{{ route('documents.index', ['type' => 'completion_acts']) }}">Акты выполненных работ</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            @if(request('type') === 'delivery_notes')
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Отображаются только активные транспортные накладные по доставляемой технике
                </div>
            @endif

            @if(request('type') === 'waybills')
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Путевые листы
                </div>
            @endif

            @if(request('type') === 'contracts')
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Договоры с платформой
                </div>
            @endif

            @if(request('type') === 'completion_acts')
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Акты выполненных работ
                </div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        @if(request('type') === 'contracts')
                            <th>№ договора</th>
                            <th>Дата заключения</th>
                            <th>Платформа</th>
                            <th>Дата начала</th>
                            <th>Дата окончания</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        @else
                            <th>№ документа</th>
                            <th>Дата</th>
                            <th>Заказ</th>
                            <th>Поставщик</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        @if(request('type') === 'contracts')
                            <!-- Для договоров -->
                            <tr>
                                <td>
                                    <strong>{{ $doc->number }}</strong>
                                </td>
                                <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                                <td>{{ $doc->platformCompany->legal_name ?? 'Платформа' }}</td>
                                <td>{{ $doc->start_date->format('d.m.Y') }}</td>
                                <td>{{ $doc->end_date->format('d.m.Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $doc->is_active ? 'success' : 'secondary' }}">
                                        {{ $doc->is_active ? 'Активен' : 'Неактивен' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('contracts.show', $doc) }}"
                                           class="btn btn-info" title="Просмотр" data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($doc->file_path)
                                        <a href="{{ route('lessee.contracts.download', $doc) }}"
                                           class="btn btn-success" title="Скачать" data-bs-toggle="tooltip">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @else
                            <!-- Для других типов документов -->
                            <tr>
                                <td>
                                    {{ $doc->number }}
                                </td>
                                <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                                <td>Заказ #{{ $doc->parent_order_id }}</td>
                                <td>
                                    {{ $doc->parentOrder->lessorCompany->legal_name ?? 'Нет данных' }}
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $doc->status_text }}
                                    </span>
                                </td>
                                <td>
                                    <!-- Кнопки просмотра и скачивания -->
                                    @if(request('type') === 'waybills')
                                        <a href="{{ route('documents.waybills.show', $doc->id) }}"
                                        class="btn btn-sm btn-outline-primary" title="Просмотреть">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @elseif(request('type') === 'completion_acts')
                                        <a href="{{ route('documents.completion-acts.show', $doc->id) }}"
                                        class="btn btn-sm btn-outline-primary" title="Просмотреть">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @elseif(request('type') === 'delivery_notes')
                                        <a href="{{ route('documents.delivery-notes.show', $doc->id) }}"
                                        class="btn btn-sm btn-outline-primary" title="Просмотреть">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif

                                    <a href="{{ url('/lessee/orders/' . $doc->parent_order_id) }}"
                                    class="btn btn-sm btn-outline-secondary" title="Перейти к заказу">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="{{ request('type') === 'contracts' ? 7 : 6 }}" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                <p>
                                    @if(request('type') === 'contracts')
                                        Договоры не найдены
                                    @elseif(request('type') === 'waybills')
                                        Путевые листы не найдены
                                    @elseif(request('type') === 'delivery_notes')
                                        Накладные не найдены
                                    @elseif(request('type') === 'completion_acts')
                                        Акты выполненных работ не найдены
                                    @else
                                        Документы не найдены
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
        <div class="card-footer">
            {{ $documents->appends(['type' => request('type')])->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endsection
