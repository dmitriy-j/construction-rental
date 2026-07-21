@extends('layouts.app')

@section('title', 'Заявка #' . $rentalRequest->id)

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.rental-requests.index') }}">Заявки</a></li>
            <li class="breadcrumb-item active">Заявка #{{ $rentalRequest->id }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">{{ $rentalRequest->title }}</h1>
        <div>
            <a href="{{ route('admin.rental-requests.edit', $rentalRequest->id) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
            <form action="{{ route('admin.rental-requests.destroy', $rentalRequest->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Удалить заявку и все связанные данные?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Основная информация -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Основная информация</h5></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Статус:</strong>
                            <span class="badge bg-{{ $rentalRequest->status_color }} ms-1">{{ $rentalRequest->status_text }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Видимость:</strong>
                            @if($rentalRequest->visibility === 'public')
                                <span class="badge bg-success ms-1">Публичная</span>
                            @else
                                <span class="badge bg-secondary ms-1">Приватная</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Создатель:</strong> {{ $rentalRequest->user?->name ?? '—' }} ({{ $rentalRequest->user?->email ?? '—' }})</div>
                        <div class="col-md-6"><strong>Компания:</strong> {{ $rentalRequest->company?->legal_name ?? '—' }} (ИНН: {{ $rentalRequest->company?->inn ?? '—' }})</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Период:</strong> {{ $rentalRequest->rental_period_start?->format('d.m.Y') }} — {{ $rentalRequest->rental_period_end?->format('d.m.Y') }}</div>
                        <div class="col-md-6"><strong>Локация:</strong> {{ $rentalRequest->location?->name ?? '—' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Бюджет:</strong> {{ number_format($rentalRequest->total_budget ?? 0, 2) }} ₽</div>
                        <div class="col-md-6"><strong>Ставка:</strong> {{ number_format($rentalRequest->hourly_rate ?? 0, 2) }} ₽/час</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong>Доставка:</strong> {{ $rentalRequest->delivery_required ? 'Да' : 'Нет' }}</div>
                        <div class="col-md-6"><strong>Дата создания:</strong> {{ $rentalRequest->created_at?->format('d.m.Y H:i') }}</div>
                    </div>
                    <div class="mb-0">
                        <strong>Описание:</strong>
                        <p class="mt-1 mb-0">{{ $rentalRequest->description ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Позиции заявки -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Позиции ({{ $rentalRequest->items->count() }})</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Категория</th>
                                <th>Кол-во</th>
                                <th>Ставка</th>
                                <th>Цена расчётная</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rentalRequest->items as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->category?->name ?? '—' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->hourly_rate ?? $rentalRequest->hourly_rate, 2) }} ₽</td>
                                    <td>{{ number_format($item->calculated_price ?? 0, 2) }} ₽</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ответы/предложения -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ответы ({{ $rentalRequest->responses->count() }})</h5>
                    <span class="text-muted small">{{ $proposalsCount }} предложений, {{ $commentsCount }} комментариев</span>
                </div>
                <div class="card-body p-0">
                    @if($rentalRequest->responses->isEmpty())
                        <div class="text-center text-muted py-3">Нет ответов</div>
                    @else
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Арендодатель</th>
                                    <th>Статус</th>
                                    <th>Цена</th>
                                    <th>Кол-во</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rentalRequest->responses as $response)
                                    <tr>
                                        <td>{{ $response->id }}</td>
                                        <td>{{ $response->lessor?->company?->legal_name ?? $response->lessor?->name ?? '—' }}</td>
                                        <td>
                                            @if($response->isComment())
                                                <span class="badge bg-secondary">Комментарий</span>
                                            @else
                                                <span class="badge bg-{{ $response->status === 'accepted' ? 'success' : ($response->status === 'rejected' ? 'danger' : 'warning') }}">
                                                    {{ $response->status }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($response->proposed_price ?? 0, 2) }} ₽</td>
                                        <td>{{ $response->proposed_quantity ?? '—' }}</td>
                                        <td>{{ $response->created_at?->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <!-- Боковая панель - управление статусом -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Изменить статус</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.rental-requests.update', $rentalRequest->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <select name="status" class="form-select">
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}" {{ $rentalRequest->status === $s ? 'selected' : '' }}>
                                        {{ \App\Models\RentalRequest::getStatusText($s) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Обновить статус
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Статистика</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>Позиций:</strong> {{ $rentalRequest->items->count() }}</li>
                        <li class="mb-2"><strong>Общее кол-во:</strong> {{ $rentalRequest->items->sum('quantity') }}</li>
                        <li class="mb-2"><strong>Предложений:</strong> {{ $proposalsCount }}</li>
                        <li class="mb-2"><strong>Комментариев:</strong> {{ $commentsCount }}</li>
                        <li><strong>Истекает:</strong> {{ $rentalRequest->expires_at?->format('d.m.Y H:i') ?? '—' }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
