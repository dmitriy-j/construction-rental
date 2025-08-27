<!-- resources/views/admin/documents/partials/contracts-index.blade.php -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Арендодатель</th>
                <th>Арендатор</th>
                <th>Дата начала</th>
                <th>Дата окончания</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $contract)
                <tr>
                    <td>{{ $contract->number }}</td>
                    <td>{{ $contract->lessorCompany->legal_name ?? 'Не указан' }}</td>
                    <td>{{ $contract->lesseeCompany->legal_name ?? 'Не указан' }}</td>
                    <td>{{ $contract->start_date->format('d.m.Y') }}</td>
                    <td>{{ $contract->end_date->format('d.m.Y') }}</td>
                    <td>
                        <span class="badge badge-{{ $contract->is_active ? 'success' : 'secondary' }}">
                            {{ $contract->is_active ? 'Активен' : 'Неактивен' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.documents.show', ['type' => 'contracts', 'id' => $contract->id]) }}"
                           class="btn btn-sm btn-info">Просмотр</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Договоры не найдены</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
