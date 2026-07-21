@extends('layouts.app')

@section('title', 'Добавление техники')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Добавление техники</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.equipment.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2">Основная информация</h5>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Название *</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Категория *</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Выберите категорию</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Бренд *</label>
                        <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand') }}" required>
                        @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Модель *</label>
                        <input type="text" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model') }}" required>
                        @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Год выпуска *</label>
                        <input type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', date('Y')) }}" required>
                        @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Наработка (часы)</label>
                        <input type="number" step="0.01" name="hours_worked" class="form-control @error('hours_worked') is-invalid @enderror" value="{{ old('hours_worked', 0) }}">
                        @error('hours_worked') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Локация *</label>
                        <div class="input-group">
                            <select name="location_id" id="locationSelect" class="form-select @error('location_id') is-invalid @enderror" required>
                                <option value="">Выберите локацию</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#locationModal">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        @error('location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Тип техники и владелец</h5>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_platform_owned" value="0">
                            <input type="checkbox" name="is_platform_owned" class="form-check-input" role="switch" id="isPlatformOwned" value="1" {{ old('is_platform_owned') ? 'checked' : '' }} onchange="toggleCompanySelect(this.checked)">
                            <label class="form-check-label" for="isPlatformOwned"><i class="bi bi-building-gear"></i> Собственная техника платформы</label>
                            <div class="form-text">Если включено — техника будет доступна без внешнего арендодателя, наценка не применяется</div>
                        </div>
                    </div>

                    <div class="col-md-6" id="companySelectWrapper">
                        <label class="form-label">Компания-арендодатель</label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" id="companySelect">
                            <option value="">Нет компании (собственная техника)</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->legal_name }}</option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Тарифы</h5>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Цена за час (₽) *</label>
                        <input type="number" step="0.01" name="price_per_hour" class="form-control @error('price_per_hour') is-invalid @enderror" value="{{ old('price_per_hour') }}" required>
                        @error('price_per_hour') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Создать технику</button>
                        <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">Отмена</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно создания локации -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая локация</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Название *</label>
                    <input type="text" id="locName" class="form-control" placeholder="г. Москва, Склад №1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Адрес</label>
                    <input type="text" id="locAddress" class="form-control" placeholder="ул. Строителей, д. 10">
                </div>
                <div class="row g-2 mb-0">
                    <div class="col-6">
                        <label class="form-label">Широта</label>
                        <input type="number" step="any" id="locLat" class="form-control" placeholder="55.7558">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Долгота</label>
                        <input type="number" step="any" id="locLng" class="form-control" placeholder="37.6173">
                    </div>
                </div>
                <div id="locError" class="alert alert-danger d-none mt-3 mb-0"></div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveLocationBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="locSpinner"></span>
                    Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCompanySelect(isPlatformOwned) {
    document.getElementById('companySelectWrapper').style.display = isPlatformOwned ? 'none' : 'block';
    if (isPlatformOwned) document.getElementById('companySelect').value = '';
}

document.getElementById('saveLocationBtn')?.addEventListener('click', function() {
    const name = document.getElementById('locName').value.trim();
    const address = document.getElementById('locAddress').value.trim();
    const latitude = document.getElementById('locLat').value;
    const longitude = document.getElementById('locLng').value;
    const errorDiv = document.getElementById('locError');
    const btn = this;
    const spinner = document.getElementById('locSpinner');
    if (!name) { errorDiv.textContent = 'Название обязательно'; errorDiv.classList.remove('d-none'); return; }
    errorDiv.classList.add('d-none');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    fetch('{{ route("admin.locations.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ name, address, latitude, longitude }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('locationSelect');
            const opt = document.createElement('option');
            opt.value = data.location.id;
            opt.textContent = data.location.name + (data.location.address ? ' (' + data.location.address + ')' : '');
            opt.selected = true;
            select.appendChild(opt);
            bootstrap.Modal.getInstance(document.getElementById('locationModal')).hide();
            document.getElementById('locName').value = '';
            document.getElementById('locAddress').value = '';
            document.getElementById('locLat').value = '';
            document.getElementById('locLng').value = '';
        } else {
            errorDiv.textContent = data.message || 'Ошибка сохранения';
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(() => { errorDiv.textContent = 'Ошибка сети'; errorDiv.classList.remove('d-none'); })
    .finally(() => { btn.disabled = false; spinner.classList.add('d-none'); });
});

document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('isPlatformOwned');
    if (checkbox) toggleCompanySelect(checkbox.checked);
});
</script>
@endsection
