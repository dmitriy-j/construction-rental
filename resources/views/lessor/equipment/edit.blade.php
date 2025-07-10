@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Редактирование техники: {{ $equipment->title }}</h1>

    <form action="{{ route('lessor.equipment.update', $equipment) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-header">Основная информация</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Название техники *</label>
                        <input type="text" name="title" class="form-control"
                               value="{{ old('title', $equipment->title) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Категория *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $equipment->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Описание *</label>
                    <textarea name="description" class="form-control" rows="3" required>{{ old('description', $equipment->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Технические характеристики</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Бренд *</label>
                        <input type="text" name="brand" class="form-control"
                               value="{{ old('brand', $equipment->brand) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Модель *</label>
                        <input type="text" name="model" class="form-control"
                               value="{{ old('model', $equipment->model) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Год выпуска *</label>
                        <input type="number" name="year" class="form-control"
                               value="{{ old('year', $equipment->year) }}"
                               min="1900" max="{{ date('Y') + 1 }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Наработка (часы) *</label>
                        <input type="number" name="hours_worked" class="form-control" step="0.1"
                               value="{{ old('hours_worked', $equipment->hours_worked) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Локация *</label>
                        <select name="location_id" class="form-select" required>
                            <option value="">Выберите локацию</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}"
                                    {{ old('location_id', $equipment->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->city }}, {{ $location->region }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Тарифы</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Цена за час (₽) *</label>
                        <input type="number" name="price_per_hour" class="form-control" step="0.01"
                               value="{{ old('price_per_hour', $prices['price_per_час'] ?? '') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Цена за смену (₽)</label>
                        <input type="number" name="price_per_shift" class="form-control" step="0.01"
                               value="{{ old('price_per_shift', $prices['price_per_смена'] ?? '') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Цена за сутки (₽)</label>
                        <input type="number" name="price_per_day" class="form-control" step="0.01"
                               value="{{ old('price_per_day', $prices['price_per_сутки'] ?? '') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Цена за месяц (₽)</label>
                        <input type="number" name="price_per_month" class="form-control" step="0.01"
                               value="{{ old('price_per_month', $prices['price_per_месяц'] ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Изображения</div>
            <div class="card-body">
                <div class="row">
                    @foreach($equipment->images as $image)
                    <div class="col-md-3 mb-3 position-relative">
                        <img src="{{ asset('storage/' . $image->path) }}" class="img-fluid rounded">

                        <div class="form-check mt-2">
                            <input type="radio" name="main_image" value="{{ $image->id }}"
                                   class="form-check-input"
                                   {{ $image->is_main ? 'checked' : '' }}>
                            <label class="form-check-label">Главное</label>
                        </div>

                        <div class="form-check mt-2">
                            <input type="checkbox" name="delete_images[]" value="{{ $image->id }}"
                                   class="form-check-input">
                            <label class="form-check-label text-danger">Удалить</label>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mb-3">
                    <label class="form-label">Добавить изображения</label>
                    <input type="file" name="images[]" class="form-control" multiple>
                    <div class="form-text">Первое изображение будет использоваться как основное</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('lessor.equipment.index') }}" class="btn btn-secondary">
                Отмена
            </a>
            <button type="submit" class="btn btn-primary">
                Сохранить изменения
            </button>
        </div>
    </form>
</div>
@endsection
