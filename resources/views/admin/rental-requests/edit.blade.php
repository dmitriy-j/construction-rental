@extends('layouts.app')

@section('title', 'Редактирование заявки #' . $rentalRequest->id)

@push('styles')
<style>
    .spec-row { border-left: 3px solid #0d6efd; margin-bottom: 4px; padding: 4px 8px; background: #f8f9fa; border-radius: 4px; }
    .item-card { border: 1px solid #dee2e6; border-radius: 0.5rem; transition: box-shadow .15s; }
    .item-card:hover { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); }
    .item-card .card-header { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 0.5rem 1rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.rental-requests.index') }}">Заявки</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.rental-requests.show', $rentalRequest->id) }}">#{{ $rentalRequest->id }}</a></li>
            <li class="breadcrumb-item active">Редактирование</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Редактирование заявки #{{ $rentalRequest->id }}</h1>
        <a href="{{ route('admin.rental-requests.show', $rentalRequest->id) }}" class="btn btn-outline-secondary">
            ← Назад
        </a>
    </div>

    <form action="{{ route('admin.rental-requests.update', $rentalRequest->id) }}" method="POST" id="editForm">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                {{-- Основные параметры --}}
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-gear me-2"></i>Основные параметры</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Название заявки <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $rentalRequest->title) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Статус</label>
                                <select name="status" class="form-select">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s }}" {{ $rentalRequest->status === $s ? 'selected' : '' }}>
                                            {{ \App\Models\RentalRequest::getStatusText($s) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Арендатор <span class="text-danger">*</span></label>
                                <select name="company_id" class="form-select" required>
                                    @foreach($companies as $c)
                                        <option value="{{ $c->id }}" {{ $rentalRequest->company_id === $c->id ? 'selected' : '' }}>
                                            {{ $c->legal_name }} (ИНН {{ $c->inn ?? '—' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Видимость</label>
                                <select name="visibility" class="form-select">
                                    <option value="public" {{ $rentalRequest->visibility === 'public' ? 'selected' : '' }}>Публичная</option>
                                    <option value="private" {{ $rentalRequest->visibility === 'private' ? 'selected' : '' }}>Приватная</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Доставка</label>
                                <select name="delivery_required" class="form-select">
                                    <option value="0" {{ !$rentalRequest->delivery_required ? 'selected' : '' }}>Нет</option>
                                    <option value="1" {{ $rentalRequest->delivery_required ? 'selected' : '' }}>Да</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Локация</label>
                                <select name="location_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ $rentalRequest->location_id === $loc->id ? 'selected' : '' }}>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Дата начала</label>
                                <input type="date" name="rental_period_start" class="form-control"
                                       value="{{ old('rental_period_start', $rentalRequest->rental_period_start?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Дата окончания</label>
                                <input type="date" name="rental_period_end" class="form-control"
                                       value="{{ old('rental_period_end', $rentalRequest->rental_period_end?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Описание</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $rentalRequest->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Позиции заявки --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Позиции</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">
                            <i class="bi bi-plus-lg"></i> Добавить позицию
                        </button>
                    </div>
                    <div class="card-body" id="itemsContainer">
                        @foreach($rentalRequest->items as $index => $item)
                            @include('admin.rental-requests._item_form', [
                                'item' => $item,
                                'index' => $index,
                                'categories' => $categories,
                            ])
                        @endforeach

                        {{-- Шаблон для JS --}}
                        <template id="itemTemplate">
                            @include('admin.rental-requests._item_form', [
                                'item' => null,
                                'index' => '__INDEX__',
                                'categories' => $categories,
                            ])
                        </template>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Информация</h5></div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-muted">ID заявки</span>
                                <strong>#{{ $rentalRequest->id }}</strong>
                            </li>
                            <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-muted">Позиций сейчас</span>
                                <strong id="itemsCount">{{ $rentalRequest->items->count() }}</strong>
                            </li>
                            <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="text-muted">Создана</span>
                                <strong>{{ $rentalRequest->created_at?->format('d.m.Y H:i') }}</strong>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Обновлена</span>
                                <strong>{{ $rentalRequest->updated_at?->format('d.m.Y H:i') }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-save me-1"></i> Сохранить изменения
                        </button>
                        <a href="{{ route('admin.rental-requests.show', $rentalRequest->id) }}" class="btn btn-outline-secondary w-100">
                            Отмена
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let itemIndex = {{ $rentalRequest->items->count() }};

function addItem() {
    const template = document.getElementById('itemTemplate');
    const html = template.innerHTML.replace(/__INDEX__/g, itemIndex);
    const container = document.getElementById('itemsContainer');
    const div = document.createElement('div');
    div.innerHTML = html;
    container.appendChild(div.firstElementChild);
    itemIndex++;
    updateCount();
}

function removeItem(btn) {
    if (confirm('Удалить эту позицию?')) {
        btn.closest('.item-card').remove();
        updateCount();
    }
}

function updateCount() {
    const count = document.querySelectorAll('.item-card').length;
    document.getElementById('itemsCount').textContent = count;
}

// Заполнение спецификаций при выборе категории
function loadSpecs(select) {
    const card = select.closest('.item-card');
    const specsContainer = card.querySelector('.specs-container');
    const categoryId = select.value;

    if (!categoryId) {
        specsContainer.innerHTML = '<small class="text-muted">Выберите категорию</small>';
        return;
    }

    fetch(`/api/categories/${categoryId}/specifications`)
        .then(r => r.json())
        .then(data => {
            // API от SpecificationController возвращает data.standard_specifications
            const specs = data?.data?.standard_specifications || data?.template || [];
            if (specs.length > 0) {
                let html = '';
                specs.forEach(spec => {
                    const key = spec.key || '';
                    const label = spec.label || spec.name || key;
                    const unit = spec.unit || '';
                    const required = spec.required || spec.is_required || false;
                    html += `
                        <div class="spec-row">
                            <small class="text-muted d-block">${label} ${required ? '<span class="text-danger">*</span>' : ''}</small>
                            <input type="text" class="form-control form-control-sm"
                                   name="items[${card.dataset.index}][specifications][${key}]"
                                   placeholder="${unit || ''}"
                                   ${required ? 'required' : ''}>
                            ${unit ? `<small class="text-muted">${unit}</small>` : ''}
                        </div>
                    `;
                });
                specsContainer.innerHTML = html;
            } else {
                specsContainer.innerHTML = '<small class="text-muted">Нет спецификаций для этой категории</small>';
            }
        })
        .catch(err => {
            console.error('Specs load error:', err);
            specsContainer.innerHTML = '<small class="text-danger">Ошибка загрузки спецификаций</small>';
        });
}

// Показать/скрыть поля индивидуальных условий
function toggleConditions(checkbox, index) {
    const fields = document.getElementById('indivCondFields' + index);
    if (fields) {
        fields.style.display = checkbox.checked ? 'block' : 'none';
    }
}

// Инициализация — проставляем дата-атрибуты для уже существующих items
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-card').forEach((card, i) => {
        if (!card.dataset.index) card.dataset.index = i;
    });
});
</script>
@endpush
