@extends('layouts.app')

@section('title', 'Управление заявками — Админ-панель')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Заявки арендаторов</h1>
            <p class="text-muted mb-0 small">Управление заявками на аренду техники</p>
        </div>
        <a href="{{ route('admin.rental-requests.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i> Создать</a>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Поиск</label>
                    <input type="text" name="search" class="form-control" placeholder="Название, пользователь, компания" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-select">
                        <option value="all">Все статусы</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ \App\Models\RentalRequest::getStatusText($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Видимость</label>
                    <select name="visibility" class="form-select">
                        <option value="">Все</option>
                        <option value="public" {{ request('visibility') == 'public' ? 'selected' : '' }}>Публичные</option>
                        <option value="private" {{ request('visibility') == 'private' ? 'selected' : '' }}>Приватные</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Сортировка</label>
                    <select name="sort" class="form-select">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Сначала новые</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Сначала старые</option>
                        <option value="budget" {{ request('sort') == 'budget' ? 'selected' : '' }}>По бюджету</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Найти</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Название</th>
                        <th>Компания</th>
                        <th>Пользователь</th>
                        <th>Статус</th>
                        <th>Видимость</th>
                        <th>Бюджет</th>
                        <th>Позиций</th>
                        <th>Предложений</th>
                        <th>Дата</th>
                        <th style="width:120px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                        <tr>
                            <td class="text-muted">{{ $request->id }}</td>
                            <td><a href="{{ route('admin.rental-requests.show', $request->id) }}" class="fw-medium text-decoration-none">{{ Str::limit($request->title, 50) }}</a></td>
                            <td>{{ $request->company?->legal_name ?? '—' }}</td>
                            <td>{{ $request->user?->name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $request->status_color }}">{{ $request->status_text }}</span></td>
                            <td><span class="badge bg-{{ $request->visibility === 'public' ? 'success' : 'secondary' }}">{{ $request->visibility === 'public' ? 'Публичная' : 'Приватная' }}</span></td>
                            <td class="fw-medium">{{ number_format($request->total_budget ?? 0, 2) }} ₽</td>
                            <td>{{ $request->items_count }}</td>
                            <td>{{ $request->responses_count }}</td>
                            <td class="small text-muted">{{ $request->created_at?->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.rental-requests.show', $request->id) }}" class="btn btn-outline-primary" title="Просмотр"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.rental-requests.edit', $request->id) }}" class="btn btn-outline-warning" title="Редактировать"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.rental-requests.destroy', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>Заявки не найдены</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif
    </div>
</div>
@endsection
