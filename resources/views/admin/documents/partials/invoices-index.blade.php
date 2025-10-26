<!-- resources/views/admin/documents/partials/invoices-index.blade.php -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Дата выставления</th>
                <th>Срок оплаты</th>
                <th>Компания</th>
                <th>Сумма</th>
                <th>Оплачено</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $invoice)
                <tr>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                    <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                    <td>{{ $invoice->company->legal_name }}</td>
                    <td>{{ number_format($invoice->amount, 2) }} ₽</td>
                    <td>{{ number_format($invoice->amount_paid, 2) }} ₽</td>
                    <td>
                        @php
                            $statusClass = match($invoice->status) {
                                'draft' => 'secondary',
                                'sent' => 'info',
                                'viewed' => 'primary',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'canceled' => 'dark',
                                default => 'light'
                            };
                        @endphp
                        <span class="badge badge-{{ $statusClass }}">
                            {{ $invoice->status }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.documents.show', ['type' => 'invoices', 'id' => $invoice->id]) }}"
                           class="btn btn-sm btn-info">Просмотр</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Счета не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $documents->links() }}
