@extends('layouts.app')

@section('title', 'Мои заявки на аренду')

@section('content')
<div class="container-fluid">
    <!-- Контейнер для Vue приложения -->
    <div id="rental-request-list-app"
         data-user-role="lessee"
         data-auth-user="{{ json_encode(auth()->user()) }}">
        <!-- Показываем заглушку пока Vue загружается -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка заявок...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/rental-requests.js')
@endpush
