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
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2 text-warning"></i>Рекомендуемые заявки
                        </h5>
                    </div>
                    <div class="card-body">
                        <rental-request-list
                            :requests="{{ json_encode($recommendedRequests->items()) }}"
                            :pagination="{{ json_encode($recommendedRequests->toArray()) }}"
                            :can-respond="true"
                            @respond="handleRespond"
                            @page-changed="handlePageChanged"
                        ></rental-request-list>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Все заявки --}}
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
                    ></rental-request-list>
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
