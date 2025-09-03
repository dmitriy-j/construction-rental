@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Счета</h3>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер счета</th>
                        <th>Компания</th>
                        <th>Заказ</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->id }}</td>
                        <td>{{ $invoice->number }}</td>
                        <td>
                            @if($invoice->company)
                                {{ $invoice->company->legal_name }}
                                <br><small class="text-muted">ИНН: {{ $invoice->company->inn }}</small>
                            @else
                                <span class="text-muted">Не указана</span>
                            @endif
                        </td>
                        <td>
                            @if($invoice->order)
                                Заказ #{{ $invoice->order->id }}
                            @else
                                <span class="text-muted">Не указан</span>
                            @endif
                        </td>
                        <td>{{ number_format($invoice->amount, 2) }} руб.</td>
                        <td>
                            <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'secondary' }}">
                                {{ $invoice->status === 'paid' ? 'Оплачен' : 'Ожидает оплаты' }}
                            </span>
                        </td>
                        <td>{{ $invoice->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.finance.invoices.show', $invoice) }}"
                               class="btn btn-info btn-sm" title="Просмотр">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Счета не найдены</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
