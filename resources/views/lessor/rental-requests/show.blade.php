{{-- resources/views/lessor/rental-requests/show.blade.php --}}
@extends('layouts.app')

@section('title', $request->title . ' - Панель арендодателя')

@section('content')
<div class="container-fluid px-4 lessor-container">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">{{ $request->title }}</h1>
                <div>
                    <a href="{{ route('lessor.rental-requests.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Назад к списку
                    </a>
                    <a href="{{ route('portal.rental-requests.show', $request->id) }}"
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-paper-plane me-2"></i>Предложить технику
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Аналитика по заявке --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Аналитика по заявке
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="stat-item">
                                <div class="stat-value text-primary">{{ $analytics['total_proposals'] ?? 0 }}</div>
                                <div class="stat-label">Всего предложений</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-item">
                                <div class="stat-value text-info">{{ $analytics['my_proposals'] ?? 0 }}</div>
                                <div class="stat-label">Ваших предложений</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-item">
                                <div class="stat-value text-success">{{ $analytics['my_accepted_proposals'] ?? 0 }}</div>
                                <div class="stat-label">Принято ваших</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value text-warning">{{ $analytics['my_conversion_rate'] ?? 0 }}%</div>
                                <div class="stat-label">Ваша конверсия</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value text-secondary">{{ $analytics['market_conversion_rate'] ?? 0 }}%</div>
                                <div class="stat-label">Конверсия рынка</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Vue компонент для детальной страницы заявки --}}
    <div id="lessor-rental-request-detail">
        <rental-request-detail
            :request="{{ json_encode($request) }}"
            :analytics="{{ json_encode($analytics) }}"
            :lessor-pricing="{{ json_encode($lessorPricing) }}"
            :proposal-history="{{ json_encode($proposalHistory) }}"
            :templates="{{ json_encode($templates) }}"
            :categories="{{ json_encode($categories) }}"
        ></rental-request-detail>
    </div>
</div>
@endsection

@push('styles')
<style>
.lessor-container {
    margin-left: 250px;
    padding: 20px;
    min-height: calc(100vh - 60px);
}

.lessor-container .page-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.price-comparison {
    font-size: 0.9rem;
}

.price-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.price-label {
    color: #6c757d;
}

.price-value {
    font-weight: 600;
}

.bg-success-light {
    background-color: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.bg-warning-light {
    background-color: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.proposal-item {
    transition: all 0.3s ease;
}

.proposal-item:hover {
    background-color: #f8f9fa;
}

.stat-item {
    text-align: center;
    padding: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .lessor-container {
        margin-left: 0;
        padding: 10px;
    }
}
</style>
@endpush

@push('scripts')
{{-- Подключаем Vue компонент для детальной страницы заявки --}}
@vite('resources/js/pages/lessor-rental-request-detail.js')
@endpush
