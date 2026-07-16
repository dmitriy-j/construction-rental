@extends('layouts.app')

@section('title', 'Управление заявками')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Заявки арендаторов</h1>
        <a href="{{ route('admin.rental-requests.create') }}" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Создать заявку
        </a>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control"
                           placeholder="Поиск (название, описание, пользователь, компания)"
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="all">Все статусы</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                                {{ __('rental_request.status_' . $s) ?: $s }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="visibility" class="form-select">
                        <option value="">Все видимости</option>
                        <option value="public" {{ request('visibility') == 'public' ? 'selected' : '' }}>Публичные</option>
                        <option value="private" {{ request('visibility') == 'private' ? 'selected' : '' }}>Приватные</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-select">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Сначала новые</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Сначала старые</option>
                        <option value="budget" {{ request('sort') == 'budget' ? 'selected' : '' }}>По бюджету</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Фильтр
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица заявок -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Компания</th>
                            <th>Пользователь</th>
                            <th>Статус</th>
                            <th>Видимость</th>
                            <th>Бюджет</th>
                            <th>Позиций</th>
                            <th>Предложений</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>
                                    <a href="{{ route('admin.rental-requests.show', $request->id) }}">
                                        {{ Str::limit($request->title, 50) }}
                                    </a>
                                </td>
                                <td>{{ $request->company?->legal_name ?? '—' }}</td>
                                <td>{{ $request->user?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->status_color }}">
                                        {{ $request->status_text }}
                                    </span>
                                </td>
                                <td>
                                    @if($request->visibility === 'public')
                                        <span class="badge bg-success">Публичная</span>
                                    @else
                                        <span class="badge bg-secondary">Приватная</span>
                                    @endif
                                </td>
                                <td>{{ number_format($request->total_budget ?? 0, 2) }} ₽</td>
                                <td>{{ $request->items_count }}</td>
                                <td>{{ $request->responses_count }}</td>
                                <td>{{ $request->created_at?->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.rental-requests.show', $request->id) }}"
                                       class="btn btn-sm btn-outline-primary" title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.rental-requests.edit', $request->id) }}"
                                       class="btn btn-sm btn-outline-warning" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.rental-requests.destroy', $request->id) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Удалить заявку и все связанные данные?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    Заявки не найдены
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
@endsection
