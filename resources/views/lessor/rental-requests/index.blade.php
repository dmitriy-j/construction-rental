{{-- resources/views/lessor/rental-requests/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Заявки на аренду - Панель арендодателя')

@section('content')
<div class="container-fluid px-4 lessor-container">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">Заявки на аренду</h1>
                <div class="stats-badge">
                    <span class="badge bg-primary">Найдено: {{ $rentalRequests->total() }} заявок</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ВРЕМЕННЫЙ HTML FALLBACK (изначально видимый) --}}
    <div id="lessor-html-fallback">
        @if($rentalRequests->count() > 0)
            @foreach($rentalRequests as $request)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ $request->title ?? 'Без названия' }}</h5>
                    <p class="card-text">{{ $request->description ?? 'Описание отсутствует' }}</p>
                    <div class="d-flex justify-content-between text-muted small">
                        <span><i class="fas fa-map-marker-alt"></i> {{ $request->location->name ?? 'Локация не указана' }}</span>
                        <span><i class="fas fa-calendar-alt"></i> {{ $request->rental_period_start->format('d.m.Y') }} - {{ $request->rental_period_end->format('d.m.Y') }}</span>
                        <span class="badge bg-primary">{{ $request->active_proposals_count ?? 0 }} предложений</span>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('lessor.rental-requests.show', $request->id) }}" class="btn btn-primary btn-sm">Подробнее</a>
                        <a href="{{ route('portal.rental-requests.show', $request->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">Предложить</a>
                    </div>
                </div>
            </div>
            @endforeach
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-inbox fa-2x mb-3"></i>
                <h5>Заявки не найдены</h5>
                <p class="text-muted">Попробуйте изменить параметры фильтрации</p>
            </div>
        @endif
    </div>

    {{-- Vue компонент для ЛК арендодателя --}}
    <div id="lessor-rental-requests-app">
        <lessor-rental-request-list
            :initial-requests="{{ json_encode($rentalRequests->items()) }}"
            :initial-analytics="{{ json_encode($analytics) }}"
            :categories="{{ json_encode($categories) }}"
            :locations="{{ json_encode($locations) }}"
            :filters="{{ json_encode($filters) }}"
        ></lessor-rental-request-list>
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

@media (max-width: 768px) {
    .lessor-container {
        margin-left: 0;
        padding: 10px;
    }
}
</style>
@endpush

@push('scripts')
{{-- Подключаем Vue компонент для ЛК арендодателя --}}
@if(app()->environment('local') && file_exists(public_path('hot')))
    @vite('resources/js/pages/lessor-rental-requests.js')
@else
    @php
        try {
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        } catch (Exception $e) {
            $manifest = [];
        }
    @endphp

    @if(isset($manifest['resources/js/pages/lessor-rental-requests.js']))
        <script type="module" src="{{ asset('build/' . $manifest['resources/js/pages/lessor-rental-requests.js']['file']) }}"></script>
    @endif
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Скрываем HTML fallback после загрузки Vue
    setTimeout(function() {
        const vueApp = document.getElementById('lessor-rental-requests-app');
        const fallback = document.getElementById('lessor-html-fallback');

        if (vueApp && vueApp.__vue_app__ && fallback) {
            fallback.style.display = 'none';
        }
    }, 100);
});
</script>
@endpush
