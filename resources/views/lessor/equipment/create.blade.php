@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Добавление новой техники</h1>

    <form action="{{ route('lessor.equipment.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card mb-4">
            <div class="card-header">Основная информация</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Название техники *</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Категория *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Описание *</label>
                    <textarea name="description" class="form-control" rows="3" required>{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Технические характеристики</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Бренд *</label>
                        <input type="text" name="brand" class="form-control" value="{{ old('brand') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Модель *</label>
                        <input type="text" name="model" class="form-control" value="{{ old('model') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Год выпуска *</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year') }}"
                               min="1900" max="{{ date('Y') + 1 }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Наработка (часы) *</label>
                        <input type="number" name="hours_worked" class="form-control" step="0.1" value="{{ old('hours_worked') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Локация *</label>
                        <select name="location_id" class="form-select" required>
                            <option value="">Выберите локацию</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->city }}, {{ $location->region }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Цена за час (₽) *</label>
                        <input type="number" name="price_per_hour" class="form-control" step="0.01" value="{{ old('price_per_hour') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Цена за смену (₽)</label>
                        <input type="number" name="price_per_shift" class="form-control" step="0.01" value="{{ old('price_per_shift', '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Цена за сутки (₽)</label>
                        <input type="number" name="price_per_day" class="form-control" step="0.01" value="{{ old('price_per_day', '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Цена за месяц (₽)</label>
                        <input type="number" name="price_per_month" class="form-control" step="0.01" value="{{ old('price_per_month', '') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Изображения</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Изображения *</label>
                    <input type="file" name="images[]" class="form-control" multiple required>
                    <div class="form-text">Первое изображение будет использоваться как основное</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('lessor.equipment.index') }}" class="btn btn-secondary">
                Отмена
            </a>
            <button type="submit" class="btn btn-primary">
                Добавить технику
            </button>
        </div>
        @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </form>
</div>
@endsection
