@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Транспортные накладные</h1>

    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle"></i> Отображаются только накладные по вашей технике
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>№ документа</th>
                        <th>Дата создания</th>
                        <th>Заказ</th>
                        <th>Получатель</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                    <tr>
                        <td>{{ $doc->document_number ?? 'Черновик #'.$doc->id }}</td>
                        <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('lessor.orders.show', $doc->order_id) }}"
                               class="text-primary font-weight-bold">
                                Заказ #{{ $doc->order_id }}
                            </a>
                        </td>
                        <td>
                            {{ $doc->receiverCompany->legal_name ?? 'Платформа' }}
                        </td>
                        <td>
                            <span class="badge
                                @if($doc->status === \App\Models\DeliveryNote::STATUS_DRAFT) badge-warning
                                @elseif($doc->status === \App\Models\DeliveryNote::STATUS_IN_TRANSIT) badge-info
                                @elseif($doc->status === \App\Models\DeliveryNote::STATUS_DELIVERED) badge-success
                                @else badge-secondary @endif">
                                {{ \App\Models\DeliveryNote::statuses()[$doc->status] ?? $doc->status }}
                            </span>
                        </td>
                        <td class="d-flex">
                            @if($doc->status === \App\Models\DeliveryNote::STATUS_DRAFT)
                                <a href="{{ route('lessor.delivery-notes.edit', $doc) }}"
                                   class="btn btn-sm btn-warning mr-2" title="Заполнить данные">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endif

                            @if($doc->status !== \App\Models\DeliveryNote::STATUS_DRAFT)
                                <div class="btn-group mr-2">
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

                            <a href="{{ route('lessor.orders.show', $doc->order_id) }}"
                               class="btn btn-sm btn-outline-secondary" title="Перейти к заказу">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-2x mb-3"></i>
                                <p>Накладные отсутствуют</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documents->hasPages())
        <div class="card-footer bg-white">
            {{ $documents->appends(['type' => request('type')])->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
