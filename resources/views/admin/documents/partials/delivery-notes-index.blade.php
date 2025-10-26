<!-- resources/views/admin/documents/partials/delivery-notes-index.blade.php -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Дата</th>
                <th>Тип</th>
                <th>Отправитель</th>
                <th>Получатель</th>
                <th>Заказ</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $note)
                <tr>
                    <td>{{ $note->document_number }}</td>
                    <td>{{ $note->issue_date->format('d.m.Y') }}</td>
                    <td>{{ \App\Models\DeliveryNote::types()[$note->type] ?? $note->type }}</td>
                    <td>{{ $note->senderCompany->legal_name ?? 'Не указан' }}</td>
                    <td>{{ $note->receiverCompany->legal_name ?? 'Не указан' }}</td>
                    <td>#{{ $note->order_id }}</td>
                    <td>
                        <span class="badge badge-{{ $note->status == 'draft' ? 'secondary' : ($note->status == 'in_transit' ? 'warning' : ($note->status == 'delivered' ? 'info' : 'success')) }}">
                            {{ \App\Models\DeliveryNote::statuses()[$note->status] ?? $note->status }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.documents.show', ['type' => 'delivery_notes', 'id' => $note->id]) }}"
                           class="btn btn-sm btn-info">Просмотр</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Транспортные накладные не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $documents->links() }}
