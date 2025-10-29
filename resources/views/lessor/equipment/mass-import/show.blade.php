{{-- resources/views/lessor/equipment/mass-import/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('lessor.equipment.index') }}">Моя техника</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('lessor.equipment.mass-import.create') }}">Массовая загрузка</a>
                    </li>
                    <li class="breadcrumb-item active">Результат импорта</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-text"></i> Результат импорта
                        </h4>
                        <span class="badge bg-{{ $import->status === 'completed' ? 'success' : ($import->status === 'failed' ? 'danger' : 'warning') }} fs-6">
                            {{ $import->status === 'completed' ? 'Завершен' : ($import->status === 'failed' ? 'Ошибка' : 'В процессе') }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Основная информация -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Файл:</th>
                                    <td>{{ $import->original_name }}</td>
                                </tr>
                                <tr>
                                    <th>Дата загрузки:</th>
                                    <td>{{ $import->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Время обработки:</th>
                                    <td>
                                        @if($import->completed_at)
                                            {{ $import->started_at->diff($import->completed_at)->format('%H:%I:%S') }}
                                        @else
                                            В процессе...
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Всего записей:</th>
                                    <td>{{ $import->total_rows }}</td>
                                </tr>
                                <tr>
                                    <th>Успешно:</th>
                                    <td>
                                        <span class="text-success fw-bold">{{ $import->successful_rows }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>С ошибками:</th>
                                    <td>
                                        @if($import->failed_rows > 0)
                                            <span class="text-danger fw-bold">{{ $import->failed_rows }}</span>
                                        @else
                                            <span class="text-success">0</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Прогресс:</th>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success"
                                                 role="progressbar"
                                                 style="width: {{ $import->progress_percentage }}%"
                                                 aria-valuenow="{{ $import->progress_percentage }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                {{ round($import->progress_percentage) }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Глобальная ошибка -->
                    @if($import->status === 'failed' && $import->error_message)
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">
                            <i class="bi bi-exclamation-triangle"></i> Ошибка импорта
                        </h5>
                        <p class="mb-0">{{ $import->error_message }}</p>
                    </div>
                    @endif

                    <!-- Детальные ошибки -->
                    @if($import->errors && count($import->errors) > 0)
                    <div class="mt-4">
                        <h5 class="text-danger mb-3">
                            <i class="bi bi-x-circle"></i> Ошибки при обработке:
                        </h5>

                        <div class="accordion" id="errorsAccordion">
                            @foreach($import->errors as $index => $error)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $index }}">
                                    <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ $index }}"
                                            aria-expanded="false"
                                            aria-controls="collapse{{ $index }}">
                                        Строка {{ $error['row'] }}:
                                        @if(isset($error['errors']['general']))
                                            {{ $error['errors']['general'][0] }}
                                        @else
                                            {{ implode(', ', array_flatten($error['errors'])) }}
                                        @endif
                                    </button>
                                </h2>
                                <div id="collapse{{ $index }}"
                                     class="accordion-collapse collapse"
                                     aria-labelledby="heading{{ $index }}"
                                     data-bs-parent="#errorsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Данные строки:</strong>
                                                <pre class="mt-2 p-2 bg-light rounded"><code>@foreach($error['data'] as $key => $value)
{{ $key }}: {{ $value }}
@endforeach</code></pre>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Ошибки валидации:</strong>
                                                <ul class="mt-2">
                                                    @foreach($error['errors'] as $field => $messages)
                                                        @foreach($messages as $message)
                                                            <li class="text-danger">
                                                                <strong>{{ $field }}:</strong> {{ $message }}
                                                            </li>
                                                        @endforeach
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Успешный импорт -->
                    @if($import->status === 'completed' && $import->successful_rows > 0)
                    <div class="mt-4">
                        <div class="alert alert-success">
                            <h5 class="alert-heading">
                                <i class="bi bi-check-circle"></i> Импорт успешно завершен!
                            </h5>
                            <p class="mb-0">
                                Успешно добавлено {{ $import->successful_rows }} единиц техники.
                                <a href="{{ route('lessor.equipment.index') }}" class="alert-link">
                                    Перейти к списку техники
                                </a>
                            </p>
                        </div>

                        <!-- Ссылки на действия -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                            <a href="{{ route('lessor.equipment.mass-import.create') }}"
                               class="btn btn-outline-primary me-md-2">
                                <i class="bi bi-plus-circle"></i> Новый импорт
                            </a>
                            <a href="{{ route('lessor.equipment.index') }}"
                               class="btn btn-primary">
                                <i class="bi bi-list-ul"></i> К списку техники
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- В процессе -->
                    @if($import->status === 'processing')
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Обработка...</span>
                        </div>
                        <h5 class="mt-3">Идет обработка данных...</h5>
                        <p>Пожалуйста, подождите. Страница автоматически обновится при завершении.</p>

                        <div class="progress mt-3" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar"
                                 style="width: {{ $import->progress_percentage }}%"
                                 aria-valuenow="{{ $import->progress_percentage }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                {{ round($import->progress_percentage) }}%
                            </div>
                        </div>
                    </div>

                    <script>
                    // Авто-обновление страницы каждые 5 секунд при обработке
                    setTimeout(function() {
                        window.location.reload();
                    }, 5000);
                    </script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.accordion-button:not(.collapsed) {
    background-color: #f8d7da;
    color: #721c24;
}

.progress {
    border-radius: 10px;
}

.table th {
    background-color: #f8f9fa;
}

pre code {
    font-size: 0.9em;
}
</style>
@endsection
