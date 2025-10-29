{{-- resources/views/profile/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Профиль компании')

@section('body-class', 'd-flex flex-column min-vh-100')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Заголовок страницы -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">
                    <i class="bi bi-person-gear me-2"></i>Профиль компании
                </h1>
                <a href="{{ route('profile.export.pdf') }}" class="btn btn-primary" target="_blank">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Экспорт реквизитов PDF
                </a>
            </div>

            <!-- Уведомления -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Произошла ошибка при сохранении данных
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Основной контент -->
        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-vcard me-2"></i>Персональные данные
                    </h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.personal-information')
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>Юридические данные компании
                    </h5>
                    <small class="text-muted">Только для просмотра</small>
                </div>
                <div class="card-body">
                    @include('profile.partials.company-legal-information')
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bank2 me-2"></i>Банковские реквизиты
                    </h5>
                    <small class="text-muted">Для взаиморасчетов</small>
                </div>
                <div class="card-body">
                    @include('profile.partials.bank-details-form')
                </div>
            </div>
        </div>

        <!-- Боковая панель -->
        <div class="col-12 col-lg-4">
            @if($auditHistory->count())
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>История изменений
                    </h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.audit-history')
                </div>
            </div>
            @endif

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Безопасность
                    </h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Опасная зона
                    </h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Простой скрипт для инициализации компонентов
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Авто-скрытие alert через 5 секунд
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
@endpush
