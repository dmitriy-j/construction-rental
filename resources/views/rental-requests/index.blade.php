@extends('layouts.app')

@section('title', 'Публичные заявки')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Заявки на аренду</h1>

    <div id="rental-requests-app"
         data-user-role="{{ $user && $user->company && $user->company->is_lessor ? 'lessor' : 'guest' }}"
         data-auth-user="{{ json_encode($user) }}">
        <!-- Fallback, пока Vue не загрузился -->
        <div class="requests-list-fallback text-center py-5" id="rental-requests-fallback">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="text-muted">Загрузка заявок...</p>
        </div>
    </div>

    <noscript>
        <div class="alert alert-info text-center">
            Для просмотра заявок включите JavaScript.
        </div>
    </noscript>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Если через 5 секунд Vue не смонтировался — показываем сообщение
    setTimeout(function() {
        const fallback = document.getElementById('rental-requests-fallback');
        const app = document.getElementById('rental-requests-app');
        if (fallback && app && !app.__vue_app__) {
            fallback.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="bi bi-inbox me-2"></i>
                    Публичные заявки не найдены.
                    <br><small class="text-muted">Если вы создавали заявки, проверьте соединение или обновите страницу.</small>
                </div>
            `;
        }
    }, 5000);
});
</script>
@endpush
@endsection
