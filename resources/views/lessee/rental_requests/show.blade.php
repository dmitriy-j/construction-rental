@extends('layouts.app')

@section('title', 'Просмотр заявки на аренду')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">Заявка на аренду: {{ $rentalRequest->title }}</h1>
                <a href="{{ route('lessee.rental-requests.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Назад к списку
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Статус:</strong>
                            <span class="badge bg-{{ $rentalRequest->status_color }}">
                                {{ $rentalRequest->status_text }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Дата создания:</strong>
                            {{ $rentalRequest->created_at->format('d.m.Y H:i') }}
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <strong>Описание:</strong>
                            <p>{{ $rentalRequest->description }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <strong>Категория:</strong>
                            <p>{{ $rentalRequest->category->name ?? 'Не указана' }}</p>
                        </div>
                        <div class="col-md-4">
                            <strong>Локация:</strong>
                            <p>{{ $rentalRequest->location->name ?? 'Не указана' }}</p>
                        </div>
                        <div class="col-md-4">
                            <strong>Период аренды:</strong>
                            <p>{{ $rentalRequest->rental_period_start->format('d.m.Y') }} - {{ $rentalRequest->rental_period_end->format('d.m.Y') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Бюджет:</strong>
                            <p>{{ number_format($rentalRequest->budget_from, 0, ',', ' ') }} - {{ number_format($rentalRequest->budget_to, 0, ',', ' ') }} ₽</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Доставка:</strong>
                            <p>{{ $rentalRequest->delivery_required ? 'Требуется' : 'Не требуется' }}</p>
                        </div>
                    </div>

                    @if($rentalRequest->desired_specifications)
                    <div class="row">
                        <div class="col-12">
                            <strong>Дополнительные требования:</strong>
                            <p>{{ is_array($rentalRequest->desired_specifications) ? ($rentalRequest->desired_specifications['description'] ?? '') : $rentalRequest->desired_specifications }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Блок с предложениями -->
            @if($rentalRequest->responses->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Предложения от арендодателей ({{ $rentalRequest->responses_count }})</h5>
                </div>
                <div class="card-body">
                    @foreach($rentalRequest->responses as $response)
                    <div class="proposal-item border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6>{{ $response->lessor->company->legal_name ?? 'Компания' }}</h6>
                                <p class="text-muted mb-1">Оборудование: {{ $response->equipment->name ?? 'Не указано' }}</p>
                                <p class="fw-bold text-primary">Цена: {{ number_format($response->proposed_price, 0, ',', ' ') }} ₽</p>
                                @if($response->message)
                                <p class="mb-1">{{ $response->message }}</p>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $response->status === 'pending' ? 'warning' : ($response->status === 'accepted' ? 'success' : 'secondary') }}">
                                    {{ $response->status === 'pending' ? 'На рассмотрении' : ($response->status === 'accepted' ? 'Принято' : 'Отклонено') }}
                                </span>
                                <p class="text-muted small mt-1">{{ $response->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                        </div>
                        @if($response->status === 'pending')
                        <div class="mt-2">
                            <button class="btn btn-sm btn-success me-2" onclick="acceptProposal({{ $response->id }})">
                                Принять предложение
                            </button>
                            <button class="btn btn-sm btn-outline-danger">
                                Отклонить
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Пока нет предложений от арендодателей. Как только появятся подходящие предложения, они отобразятся здесь.
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Статистика заявки -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Статистика заявки</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Просмотры:</span>
                        <strong>{{ $rentalRequest->views_count }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Предложения:</span>
                        <strong>{{ $rentalRequest->responses_count }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Истекает:</span>
                        <strong>{{ $rentalRequest->expires_at ? $rentalRequest->expires_at->format('d.m.Y') : 'Не указано' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Действия -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Действия</h6>
                </div>
                <div class="card-body">
                    @if($rentalRequest->status === 'active')
                    <button class="btn btn-warning btn-sm w-100 mb-2">
                        <i class="fas fa-pause me-2"></i>Приостановить заявку
                    </button>
                    <button class="btn btn-danger btn-sm w-100">
                        <i class="fas fa-times me-2"></i>Отменить заявку
                    </button>
                    @endif

                    @if($rentalRequest->status === 'processing')
                    <div class="alert alert-success">
                        <i class="fas fa-check me-2"></i>
                        Заявка в процессе обработки. Ожидайте подтверждения.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function acceptProposal(proposalId) {
    if (confirm('Вы уверены, что хотите принять это предложение?')) {
        fetch(`/lessee/rental-requests/{{ $rentalRequest->id }}/proposals/${proposalId}/accept`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Предложение принято!');
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            alert('Произошла ошибка: ' + error.message);
        });
    }
}
</script>
@endpush
