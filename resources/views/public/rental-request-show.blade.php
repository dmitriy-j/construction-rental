@extends('layouts.app')

@section('title', 'Публичная заявка на аренду')

@section('content')
    <div class="container-fluid py-4">
        <div id="public-rental-request-show-app">
            <public-rental-request-show />
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Передаем данные из Laravel в Vue
        window.publicRentalRequestData = {
            requestId: {{ $rentalRequestId }},
            csrfToken: '{{ csrf_token() }}',
            authUser: @json(auth()->user()),
            appUrl: '{{ config('app.url') }}'
        };
    </script>
@endpush
