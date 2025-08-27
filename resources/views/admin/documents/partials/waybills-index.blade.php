<!-- resources/views/admin/documents/partials/waybills-index.blade.php -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Оборудование</th>
                <th>Оператор</th>
                <th>Период</th>
                <th>Часы работы</th>
                <th>Статус</th>
                <th>Перспектива</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $waybill)
                <tr>
                    <td>{{ $waybill->number }}</td>
                    <td>{{ $waybill->equipment->title ?? 'Не указано' }}</td>
                    <td>{{ $waybill->operator->full_name ?? 'Не назначен' }}</td>
                    <td>{{ $waybill->start_date->format('d.m.Y') }} - {{ $waybill->end_date->format('d.m.Y') }}</td>
                    <td>{{ $waybill->total_hours }}</td>
                    <td>
                        <span class="badge badge-{{ $waybill->status == 'future' ? 'secondary' : ($waybill->status == 'active' ? 'success' : 'primary') }}">
                            {{ $waybill->status_text }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $waybill->perspective == 'lessor' ? 'info' : 'warning' }}">
                            {{ $waybill->perspective == 'lessor' ? 'Арендодатель' : 'Арендатор' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.documents.show', ['type' => 'waybills', 'id' => $waybill->id]) }}"
                           class="btn btn-sm btn-info">Просмотр</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Путевые листы не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $documents->links() }}
