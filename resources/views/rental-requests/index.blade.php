@extends('layouts.app')

@section('title', 'Заявки на аренду')

@section('content')
<div class="container-fluid py-4">
    <div id="unified-requests-app"
         data-user-role="{{ $userRole }}"
         data-auth-user="{{ $user ? json_encode($user->load('company')) : 'null' }}"
         data-categories="{{ json_encode($categories) }}"
         data-locations="{{ json_encode($locations) }}">
        <!-- Fallback, пока Vue не загрузился -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="text-muted">Загрузка заявок...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/unified-requests.js')
@endpush
