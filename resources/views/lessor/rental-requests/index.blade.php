@extends('layouts.app')

@section('title', 'Заявки на аренду')

@section('content')
<div class="container-fluid px-4">
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

    {{-- Компонент поиска --}}
    <rental-request-search
        :initial-categories="{{ json_encode($categories) }}"
        :initial-locations="{{ json_encode($locations) }}"
        :initial-filters="{{ json_encode($filters) }}"
        @filters-changed="handleFiltersChanged"
    ></rental-request-search>

    {{-- Рекомендуемые заявки --}}
    @if(isset($recommendedRequests) && $recommendedRequests->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <!-- ... остальной HTML ... -->
                    <div class="card-body">
                        @if(method_exists($recommendedRequests, 'items'))
                            <rental-request-list
                                :requests="{{ json_encode($recommendedRequests->items()) }}"
                                :pagination="{{ json_encode($recommendedRequests->toArray()) }}"
                                :can-respond="true"
                                @respond="handleRespond"
                                @page-changed="handlePageChanged"
                            ></rental-request-list>
                        @else
                            <rental-request-list
                                :requests="{{ json_encode($recommendedRequests) }}"
                                :pagination="{{ json_encode([]) }}"
                                :can-respond="true"
                                @respond="handleRespond"
                                @page-changed="handlePageChanged"
                            ></rental-request-list>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Временный код для проверки --}}
<div class="container">
    <h1>Тест: Найдено заявок (коллекция)</h1>
    <p>{{ $rentalRequests->count() }}</p>

    <h1>Тест: Найдено заявок (пагинатор)</h1>
    <p>{{ $rentalRequests->total() }}</p>

    <hr>
    <h2>Содержимое первой заявки (дамп):</h2>
    @if($rentalRequests->count() > 0)
        <pre>{{ print_r($rentalRequests->first()->toArray(), true) }}</pre>
    @else
        <p>Нет заявок для отображения.</p>
    @endif
</div>

<div class="row">
    @foreach ($rentalRequests->items() as $request)
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $request->title }}</h5>
                    <p class="card-text">{{ $request->description }}</p>
                    <p class="card-text"><small class="text-muted">Бюджет: {{ $request->total_budget }} руб.</small></p>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Упрощенный вывод через Vue
<rental-request-list
    :requests="{{ json_encode($rentalRequests->items()) }}"
    :can-respond="true"
>
</rental-request-list>
    {{-- Все заявки
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Все заявки</h5>
                </div>
                <div class="card-body">
                    <rental-request-list
                        :requests="{{ json_encode($rentalRequests->items()) }}"
                        :pagination="{{ json_encode($rentalRequests->toArray()) }}"
                        :can-respond="true"
                        :loading="false"
                        @respond="handleRespond"
                        @page-changed="handlePageChanged"
                        @reset-filters="handleResetFilters"
                    ></rental-request-list>--}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Инициализация Vue приложения
const app = Vue.createApp({
    data() {
        return {
            filters: @json($filters),
            loading: false
        }
    },

    methods: {
        handleFiltersChanged(newFilters) {
            this.filters = newFilters;
            this.loadRequests();
        },

        handlePageChanged(page) {
            this.filters.page = page;
            this.loadRequests();
        },

        handleResetFilters() {
            this.filters = {
                category_id: '',
                location_id: '',
                budget_max: '',
                sort_by: 'newest',
                page: 1
            };
            this.loadRequests();
        },

        handleRespond(request) {
            window.location.href = `/lessor/rental-requests/${request.id}/proposals/create`;
        },

        loadRequests() {
            this.loading = true;

            const queryString = new URLSearchParams(this.filters).toString();

            fetch(`/lessor/rental-requests?${queryString}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Обновляем список заявок
                this.$refs.requestList.updateRequests(data.requests);
                this.loading = false;
            })
            .catch(error => {
                console.error('Error:', error);
                this.loading = false;
            });
        }
    }
});

// Регистрация компонентов
app.component('rental-request-search', RentalRequestSearch);
app.component('rental-request-list', RentalRequestList);

// Монтируем приложение
app.mount('#app');
</script>
@endpush
