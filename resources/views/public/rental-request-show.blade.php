@extends('layouts.app')

@section('title', 'Публичная заявка на аренду')

@section('content')
    <div class="container-fluid py-4">
        {{-- 🔥 УБЕДИТЕСЬ, что этот элемент только один --}}
        <div id="public-rental-request-show-app" data-request-id="{{ $rentalRequestId }}">
            {{-- Временный контент --}}
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2">Загрузка заявки...</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- 🔥 ПОДКЛЮЧАЕМ ТОЛЬКО ЭТОТ СКРИПТ --}}
    @vite(['resources/js/pages/public-rental-request-show.js'])
@endpush
