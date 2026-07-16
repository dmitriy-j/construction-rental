@extends('layouts.app')

@section('title', 'Редактирование техники')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Редактирование: {{ $equipment->title }}</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.equipment.update', $equipment) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <!-- Основная информация -->
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2">Основная информация</h5>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Название *</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $equipment->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Категория</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $equipment->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Бренд *</label>
                        <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                               value="{{ old('brand', $equipment->brand) }}" required>
                        @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Модель *</label>
                        <input type="text" name="model" class="form-control @error('model') is-invalid @enderror"
                               value="{{ old('model', $equipment->model) }}" required>
                        @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Год *</label>
                        <input type="number" name="year" class="form-control @error('year') is-invalid @enderror"
                               value="{{ old('year', $equipment->year) }}" required>
                        @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Наработка (час) *</label>
                        <input type="number" step="0.01" name="hours_worked"
                               class="form-control @error('hours_worked') is-invalid @enderror"
                               value="{{ old('hours_worked', $equipment->hours_worked) }}" required>
                        @error('hours_worked') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Рейтинг</label>
                        <input type="number" step="0.1" min="0" max="5" name="rating"
                               class="form-control @error('rating') is-invalid @enderror"
                               value="{{ old('rating', $equipment->rating ?? 0) }}">
                        @error('rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Просмотры</label>
                        <input type="number" name="views" class="form-control @error('views') is-invalid @enderror"
                               value="{{ old('views', $equipment->views ?? 0) }}">
                        @error('views') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Локация *</label>
                        <select name="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id', $equipment->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="3">{{ old('description', $equipment->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Тип техники -->
                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Тип техники и владелец</h5>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_platform_owned" value="0">
                            <input type="checkbox" name="is_platform_owned" class="form-check-input" role="switch"
                                   id="isPlatformOwned" value="1"
                                   {{ old('is_platform_owned', $equipment->is_platform_owned) ? 'checked' : '' }}
                                   onchange="toggleCompanySelect(this.checked)">
                            <label class="form-check-label" for="isPlatformOwned">
                                <i class="bi bi-building-gear"></i>
                                Собственная техника платформы
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4" id="companySelectWrapper">
                        <label class="form-label">Компания-арендодатель</label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror"
                                id="companySelect">
                            <option value="">Нет компании (собственная техника)</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id', $equipment->company_id) == $company->id ? 'selected' : '' }}>
                                    {{ $company->legal_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Статус одобрения</label>
                        <select name="is_approved" class="form-select">
                            <option value="1" {{ old('is_approved', $equipment->is_approved) ? 'selected' : '' }}>Одобрено</option>
                            <option value="0" {{ old('is_approved', $equipment->is_approved) ? '' : 'selected' }}>На проверке</option>
                        </select>
                    </div>

                    <!-- Тарифы -->
                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Тарифы</h5>
                    </div>

                    @php
                        $term = $equipment->rentalTerms->first();
                    @endphp

                    <div class="col-md-3">
                        <label class="form-label">Цена за час (₽) *</label>
                        <input type="number" step="0.01" name="price_per_hour"
                               class="form-control @error('price_per_hour') is-invalid @enderror"
                               value="{{ old('price_per_hour', $term->price_per_hour ?? 0) }}" required>
                        @error('price_per_hour') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Цена за км (₽)</label>
                        <input type="number" step="0.01" name="price_per_km"
                               class="form-control"
                               value="{{ old('price_per_km', $term->price_per_km ?? 0) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Мин. часов аренды</label>
                        <input type="number" name="min_rental_hours"
                               class="form-control"
                               value="{{ old('min_rental_hours', $term->min_rental_hours ?? 1) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Дней на доставку</label>
                        <input type="number" name="delivery_days"
                               class="form-control"
                               value="{{ old('delivery_days', $term->delivery_days ?? 1) }}">
                    </div>

                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Сохранить изменения
                        </button>
                        <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">Отмена</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCompanySelect(isPlatformOwned) {
    const wrapper = document.getElementById('companySelectWrapper');
    const select = document.getElementById('companySelect');
    if (isPlatformOwned) {
        wrapper.style.display = 'none';
        select.value = '';
    } else {
        wrapper.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('isPlatformOwned');
    toggleCompanySelect(checkbox.checked);
});
</script>
@endsection
