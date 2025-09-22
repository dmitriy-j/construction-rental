@extends('layouts.app')

@section('title', 'Мои заявки на аренду')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">Мои заявки на аренду</h1>
                <a href="{{ route('lessee.rental-requests.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Создать заявку
                </a>
            </div>

            {{-- Статистика --}}
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Всего заявок</div>
                                    <div class="h5 mb-0">{{ $stats['total'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Активные</div>
                                    <div class="h5 mb-0">{{ $stats['active'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-play-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">В процессе</div>
                                    <div class="h5 mb-0">{{ $stats['processing'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cogs fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-info text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Завершенные</div>
                                    <div class="h5 mb-0">{{ $stats['completed'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Фильтры --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Все статусы</option>
                                <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Активные</option>
                                <option value="processing" {{ $status == 'processing' ? 'selected' : '' }}>В процессе</option>
                                <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Завершенные</option>
                                <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Отмененные</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" placeholder="Поиск по названию..." id="searchInput">
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-outline-secondary" type="button" id="resetFilters">
                                <i class="fas fa-redo me-2"></i>Сбросить
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Список заявок --}}
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="requestsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Название заявки</th>
                                    <th>Категория</th>
                                    <th>Период аренды</th>
                                    <th>Бюджет</th>
                                    <th>Статус</th>
                                    <th>Предложения</th>
                                    <th>Дата создания</th>
                                    <th width="120">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                    <tr>
                                        <td>#{{ $request->id }}</td>
                                        <td>
                                            <a href="{{ route('lessee.rental-requests.show', $request->id) }}"
                                               class="text-decoration-none fw-bold">
                                                {{ Str::limit($request->title, 50) }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ Str::limit($request->description, 70) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $request->category->name }}</span>
                                        </td>
                                        <td>
                                            <small>
                                                {{ $request->rental_period_start->format('d.m.Y') }}<br>
                                                {{ $request->rental_period_end->format('d.m.Y') }}
                                            </small>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($request->budget_from, 0, ',', ' ') }} -
                                            {{ number_format($request->budget_to, 0, ',', ' ') }} ₽</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $request->status_color }}">{{ $request->status_text }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary rounded-pill me-2">{{ $request->responses_count }}</span>
                                                @if($request->responses_count > 0)
                                                    <small class="text-success">
                                                        <i class="fas fa-eye me-1"></i>{{ $request->views_count }}
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small>{{ $request->created_at->format('d.m.Y H:i') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('lessee.rental-requests.show', $request->id) }}"
                                                   class="btn btn-outline-primary" title="Просмотр">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($request->status == 'draft')
                                                    <button class="btn btn-outline-success" title="Активировать">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Удалить">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                                <h5>Заявки не найдены</h5>
                                                <p>Создайте первую заявку на аренду техники</p>
                                                <a href="{{ route('lessee.rental-requests.create') }}" class="btn btn-primary">
                                                    Создать заявку
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($requests->hasPages())
                    <div class="card-footer">
                        {{ $requests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фильтрация таблицы
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const resetFilters = document.getElementById('resetFilters');

    function filterTable() {
        const status = statusFilter.value;
        const searchText = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('#requestsTable tbody tr');

        rows.forEach(row => {
            const statusBadge = row.querySelector('.badge').textContent.toLowerCase();
            const title = row.cells[1].textContent.toLowerCase();
            const description = row.cells[1].textContent.toLowerCase();

            const statusMatch = status === 'all' || statusBadge.includes(status);
            const searchMatch = !searchText || title.includes(searchText) || description.includes(searchText);

            row.style.display = statusMatch && searchMatch ? '' : 'none';
        });
    }

    statusFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);
    resetFilters.addEventListener('click', function() {
        statusFilter.value = 'all';
        searchInput.value = '';
        filterTable();
    });
});
</script>
@endpush
