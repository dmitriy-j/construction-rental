@extends('layouts.app')

@section('title', 'Ремонт - В разработке')
@section('page-title', 'Ремонт строительной техники')
@section('background-text', 'Сервисное обслуживание и ремoнт')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <!-- Иконка -->
                    <div class="mb-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                            <i class="bi bi-tools fs-1 text-primary"></i>
                        </div>
                    </div>

                    <!-- Заголовок -->
                    <h2 class="h3 mb-3">Страница в разработке</h2>

                    <!-- Описание -->
                    <p class="text-muted mb-4">
                        Раздел "Ремонт" находится в стадии активной разработки.
                        Мы работаем над созданием удобного сервиса для обслуживания
                        и ремонта строительной техники.
                    </p>

                    <!-- Дополнительная информация -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle me-3 fs-5"></i>
                            <div>
                                <strong>Ожидайте обновления!</strong><br>
                                В ближайшее время здесь появится функционал для
                                заказа ремонтных работ и сервисного обслуживания.
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            <i class="bi bi-house me-2"></i>На главную
                        </a>
                        <a href="{{ url('/catalog') }}" class="btn btn-outline-primary">
                            <i class="bi bi-truck me-2"></i>Каталог техники
                        </a>
                        <a href="mailto:support@constructionrental.ru" class="btn btn-outline-secondary">
                            <i class="bi bi-envelope me-2"></i>Написать в поддержку
                        </a>
                    </div>

                    <!-- Прогресс разработки -->
                    <div class="mt-5">
                        <div class="d-flex justify-content-between mb-2 small text-muted">
                            <span>Статус разработки</span>
                            <span>65%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width: 65%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Дополнительная информация -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="bi bi-clock-history text-primary fs-2 mb-3"></i>
                            <h6 class="card-title">Ориентировочный срок</h6>
                            <p class="card-text small text-muted">
                                Запуск планируется<br>в ближайшие 2-3 недели
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="bi bi-telephone text-primary fs-2 mb-3"></i>
                            <h6 class="card-title">Актуальные услуги</h6>
                            <p class="card-text small text-muted">
                                Для срочного ремонта<br>звоните: +7 (999) 123-45-67
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    0% {
        background-position: 1rem 0;
    }
    100% {
        background-position: 0 0;
    }
}

.card {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>
@endsection
