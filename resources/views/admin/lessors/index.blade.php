@extends('layouts.app')

@section('title', 'Арендодатели')

@section('content')
<div class="container-fluid">
    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control"
                           placeholder="Поиск (название, ИНН, директор)"
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Все статусы</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ $status == 'verified' ? 'Подтверждённые' : ($status == 'rejected' ? 'Отклонённые' : 'Ожидают проверки') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Фильтр
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.lessors.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Сбросить
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список компаний-арендодателей</h5>
            <span>Всего: {{ $lessors->total() }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Юр. название</th>
                            <th>ИНН</th>
                            <th>Телефон</th>
                            <th>Статус</th>
                            <th>Техники</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lessors as $lessor)
                        <tr>
                            <td>{{ $lessor->id }}</td>
                            <td>
                                <strong>{{ $lessor->legal_name }}</strong>
                                @if($lessor->director_name)
                                    <div class="text-muted small">{{ $lessor->director_name }}</div>
                                @endif
                            </td>
                            <td>{{ $lessor->inn }}</td>
                            <td>{{ $lessor->phone }}</td>
                            <td>
                                <span class="badge bg-{{
                                    $lessor->status == 'verified' ? 'success' :
                                    ($lessor->status == 'rejected' ? 'danger' : 'warning')
                                }}">
                                    {{ $lessor->status == 'verified' ? 'Подтверждён' : ($lessor->status == 'rejected' ? 'Отклонён' : 'Ожидает проверки') }}
                                </span>
                                @if($lessor->status == 'rejected' && $lessor->rejection_reason)
                                    <div class="text-danger small mt-1">{{ Str::limit($lessor->rejection_reason, 30) }}</div>
                                @endif
                            </td>
                            <td>{{ $lessor->equipment_count }}</td>
                            <td>{{ $lessor->created_at->format('d.m.Y') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.lessors.show', $lessor) }}"
                                   class="btn btn-sm btn-info" title="Подробнее">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.lessors.edit', $lessor) }}"
                                   class="btn btn-sm btn-warning" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($lessor->status !== 'verified')
                                    <form action="{{ route('admin.lessors.verify', $lessor) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="verify">
                                        <button type="submit" class="btn btn-sm btn-success" title="Подтвердить"
                                                onclick="return confirm('Подтвердить компанию?')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($lessor->status !== 'rejected')
                                    <button type="button" class="btn btn-sm btn-danger" title="Отклонить"
                                            onclick="showRejectForm('{{ route('admin.lessors.verify', $lessor) }}')">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $lessors->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal для отклонения -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="rejectForm">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div class="modal-header">
                    <h5 class="modal-title">Отклонение компании</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Причина отклонения *</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required
                              placeholder="Укажите причину отклонения..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Отклонить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectForm(url) {
    document.getElementById('rejectForm').action = url;
    var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endsection
