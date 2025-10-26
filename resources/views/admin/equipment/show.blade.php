@extends('layouts.app')

@section('title', $equipment->title)

@section('content')
<div class="container py-4">
    <!-- Хлебные крошки -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.equipment.index') }}">Каталог</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $equipment->title }}</li>
        </ol>
    </nav>

    <form method="POST" action="{{ route('admin.equipment.update', $equipment->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Заголовок и кнопки -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <input type="text" name="title" value="{{ $equipment->title }}" 
                       class="form-control form-control-lg">
            </h1>
            <div class="btn-group">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Сохранить
                </button>
                <a href="{{ route('admin.equipment.show', $equipment->id) }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Отмена
                </a>
                <a href="{{ route('admin.equipment.index') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> В каталог
                </a>
            </div>
        </div>

        <!-- Основная карточка -->
        <div class="card tech-card mb-4">
            <div class="row g-0">
                <!-- Галерея изображений -->
                <div class="col-md-6">
                    <div class="p-3">
                        @if($equipment->images && $equipment->images->isNotEmpty())
                            @php
                                $mainImage = $equipment->images->firstWhere('is_main', true) ?? $equipment->images->first();
                            @endphp
                            <div class="main-image mb-3 text-center">
                                <img src="{{ asset('storage/' . $mainImage->path) }}" 
                                     class="img-fluid rounded" 
                                     alt="{{ $equipment->title }}"
                                     style="max-height: 300px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Обновить изображения:</label>
                                <input type="file" name="images[]" multiple class="form-control">
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label">Добавить изображения:</label>
                                <input type="file" name="images[]" multiple class="form-control">
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Информация -->
                <div class="col-md-6">
                    <div class="card-body">
                       <td>
                                <span class="badge bg-{{ $equipment->is_approved ? 'success' : 'warning' }}">
                                    {{ $equipment->is_approved ? 'Одобрено' : 'На проверке' }}
                                </span>
                            </td>
                            <td>{{ $equipment->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-nowrap">
                                @if(!$equipment->is_approved)
                                    <a href="{{ route('admin.equipment.approve', $equipment) }}" 
                                       class="btn btn-sm btn-success"
                                       title="Одобрить">
                                        <i class="bi bi-check-lg"></i>
                                    </a>
                                @else
                                    <a href="{{ route('admin.equipment.reject', $equipment) }}" 
                                       class="btn btn-sm btn-warning"
                                       title="Отклонить">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                @endif
                                
                            </td>
                            
                        </div>

                        <!-- Характеристики -->
                        <div class="mb-4">
                            <div class="row g-3">
                                <!-- Компания -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Компания</label>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">{{ $equipment->company->legal_name }}</span>
                                        <select name="company_id" class="form-select flex-grow-1">
                                            @foreach($companies as $company)
                                                <option value="{{ $company->id }}" 
                                                    {{ $equipment->company_id == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Основные поля -->
                                <div class="col-md-6">
                                    <label class="form-label">Бренд</label>
                                    <input type="text" name="brand" value="{{ $equipment->brand }}" 
                                           class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Модель</label>
                                    <input type="text" name="model" value="{{ $equipment->model }}" 
                                           class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Год выпуска</label>
                                    <input type="number" name="year" value="{{ $equipment->year }}" 
                                           class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Наработка (часы)</label>
                                    <input type="number" step="1" name="hours_worked" 
                                           value="{{ $equipment->hours_worked }}" 
                                           class="form-control">
                                </div>
                                
                                <!-- Рейтинг -->
                                <div class="col-md-6">
                                    <label class="form-label">Рейтинг</label>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= floor($equipment->rating))
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                @elseif($i - 0.5 <= $equipment->rating)
                                                    <i class="bi bi-star-half text-warning"></i>
                                                @else
                                                    <i class="bi bi-star text-warning"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <input type="number" name="rating" 
                                               value="{{ old('rating', $equipment->rating) }}" 
                                               min="0" max="5" step="0.1" 
                                               class="form-control" style="width: 80px;">
                                    </div>
                                </div>
                                
                                <!-- Просмотры -->
                                <div class="col-md-6">
                                    <label class="form-label">Просмотры</label>
                                    <input type="number" name="views" 
                                           value="{{ old('views', $equipment->views) }}" 
                                           min="0" class="form-control">
                                </div>
                                
                                <!-- Локация -->
                                <div class="col-md-12">
                                    <label class="form-label">Локация</label>
                                    <select name="location_id" class="form-select">
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" 
                                                {{ $equipment->location_id == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Тарифы аренды -->
                        <h5 class="card-title mb-3">Условия аренды</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Параметр</th>
                                        <th>Значение</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($equipment->rentalTerms ?? [] as $term)
                                        <tr>
                                            <td>Цена за час ({{ $term->currency }})</td>
                                            <td>
                                                <input type="number" step="0.01" name="price_per_hour" 
                                                       value="{{ $term->price_per_hour }}" 
                                                       class="form-control form-control-sm">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Цена за км ({{ $term->currency }})</td>
                                            <td>
                                                <input type="number" step="0.01" name="price_per_km" 
                                                       value="{{ $term->price_per_km }}" 
                                                       class="form-control form-control-sm">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Мин. время аренды (часы)</td>
                                            <td>
                                                <input type="number" name="min_rental_hours" 
                                                       value="{{ $term->min_rental_hours }}" 
                                                       class="form-control form-control-sm">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Срок доставки (дни)</td>
                                            <td>
                                                <input type="number" name="delivery_days" 
                                                       value="{{ $term->delivery_days }}" 
                                                       class="form-control form-control-sm">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center">Нет данных</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Описание -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Описание</h5>
            </div>
            <div class="card-body">
                <textarea name="description" class="form-control" rows="4">{{ $equipment->description }}</textarea>
            </div>
        </div>
    </form>
</div>

<style>
    .tech-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .form-control, .form-select {
        margin-bottom: 0.5rem;
    }
    .btn-group .btn {
        margin-left: 0.5rem;
    }
    .main-image img {
        max-width: 100%;
        height: auto;
    }
    .table-sm td, .table-sm th {
        padding: 0.5rem;
    }
</style>
@endsection