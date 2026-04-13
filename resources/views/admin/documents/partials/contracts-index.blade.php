<!-- resources/views/admin/documents/partials/contracts-index.blade.php -->
<div class="table-responsive">
    <!-- Кнопка управления договорами -->
    <div class="mb-3">
        <a href="{{ route('admin.contracts.index') }}" class="btn btn-primary">
            <i class="fas fa-cog"></i> Управление договорами
        </a>
    </div>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Тип договора</th>
                <th>Контрагент</th>
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
                    <td>
                        <span class="badge badge-{{ $contract->counterparty_type === 'lessor' ? 'info' : 'warning' }}">
                            {{ $contract->counterparty_type === 'lessor' ? 'С арендодателем' : 'С арендатором' }}
                        </span>
                    </td>
                    <td>{{ $contract->counterpartyCompany->legal_name ?? 'Не указан' }}</td>
                    <td>{{ $contract->start_date->format('d.m.Y') }}</td>
                    <td>{{ $contract->end_date->format('d.m.Y') }}</td>
                    <td>
                        <span class="badge badge-{{ $contract->is_active ? 'success' : 'secondary' }}">
                            {{ $contract->is_active ? 'Активен' : 'Неактивен' }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.contracts.show', $contract) }}"
                               class="btn btn-info" title="Просмотр">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.contracts.edit', $contract) }}"
                               class="btn btn-warning" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($contract->file_path)
                            <a href="{{ route('admin.contracts.download', $contract) }}"
                               class="btn btn-success" title="Скачать">
                                <i class="fas fa-download"></i>
                            </a>
                            @endif
                        </div>
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
