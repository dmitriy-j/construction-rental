@extends('layouts.app')
@php use App\Models\DeliveryNote; @endphp
@section('content')
<div class="container">
    <h1>Документы</h1>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'contracts' ? 'active' : '' }}"
               href="?type=contracts">Договоры</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'waybills' ? 'active' : '' }}"
               href="?type=waybills">Путевые листы</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'delivery_notes' ? 'active' : '' }}"
               href="?type=delivery_notes">Накладные</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('type') === 'completion_acts' ? 'active' : '' }}"
               href="?type=completion_acts">Акты выполненных работ</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            @if(request('type') === 'delivery_notes')
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Отображаются только активные транспортные накладные по доставляемой технике
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
                        <td>{{ $doc->document_number ?? 'Черновик' }}</td>
                        <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                        <td>Заказ #{{ $doc->order_id }}</td>
                        <td>{{ $doc->senderCompany->legal_name ?? 'Платформа' }}</td>
                        <td>
                            <span class="badge bg-info">
                              {{ $doc->status_text }}
                            </span>
                        </td>
                        <td>
                            {{-- Изменяем условие: показываем для всех статусов кроме черновика --}}
                            @if($doc->status !== \App\Models\DeliveryNote::STATUS_DRAFT)
                                <div class="btn-group">
                                    <a href="{{ route('delivery-notes.export.excel', $doc) }}"
                                    class="btn btn-sm btn-success" title="Excel">
                                        <i class="fas fa-file-excel"></i>
                                    </a>
                                    <a href="{{ route('delivery-notes.export.pdf', $doc) }}"
                                    class="btn btn-sm btn-danger" title="PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </div>
                            @endif

                            <a href="{{ route('lessee.orders.show', $doc->order_id) }}"
                            class="btn btn-sm btn-outline-secondary" title="Перейти к заказу">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            @if(request('type') === 'delivery_notes')
                                Активные транспортные накладные отсутствуют
                            @else
                                Документы не найдены
                            @endif
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
