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

            @if(request('type') === 'completion_acts')
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Акты выполненных работ
                </div>
            @endif

           <table class="table">
                <thead>
                    <tr>
                        <th>№ документа</th>
                        <th>Дата</th>
                        <th>Заказ</th>
                        <th>Поставщик</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                    <tr>
                        <td>
                            {{ $doc->number }}
                        </td>
                        <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                        <td>Заказ #{{ $doc->parent_order_id }}</td> <!-- Используем родительский заказ -->
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
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            Документы не найдены
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
});
</script>
@endsection
