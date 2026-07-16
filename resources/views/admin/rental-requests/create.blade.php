@extends('layouts.app')

@section('title', 'Создание заявки')

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.rental-requests.index') }}">Заявки</a></li>
            <li class="breadcrumb-item active">Создание</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4">Создание заявки от имени компании</h1>

    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                Заявка будет создана от первого найденного пользователя с ролью арендатора в выбранной компании.
                После создания заявки вы сможете просмотреть её в списке.
            </p>

            <form action="{{ route('admin.rental-requests.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Название <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                        @error('title') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Локация <span class="text-danger">*</span></label>
                        <select name="location_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Компания-арендатор <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach(\App\Models\Company::where('is_lessee', true)->get() as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->legal_name }} (ИНН: {{ $company->inn }})
                                </option>
                            @endforeach
                        </select>
                        @error('company_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ставка (₽/час) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="hourly_rate" class="form-control"
                               value="{{ old('hourly_rate') }}" required>
                        @error('hourly_rate') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Видимость</label>
                        <select name="visibility" class="form-select">
                            <option value="public" {{ old('visibility') == 'public' ? 'selected' : '' }}>Публичная</option>
                            <option value="private" {{ old('visibility') == 'private' ? 'selected' : '' }}>Приватная</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Дата начала <span class="text-danger">*</span></label>
                        <input type="date" name="rental_period_start" class="form-control"
                               value="{{ old('rental_period_start') }}" required>
                        @error('rental_period_start') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Дата окончания <span class="text-danger">*</span></label>
                        <input type="date" name="rental_period_end" class="form-control"
                               value="{{ old('rental_period_end') }}" required>
                        @error('rental_period_end') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Описание <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
                    @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <hr>
                <h5>Позиции заявки</h5>
                <p class="text-muted small">Добавьте хотя бы одну позицию с категорией и количеством.</p>

                <div id="items-container">
                    <div class="item-row row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Категория <span class="text-danger">*</span></label>
                            <select name="items[0][category_id]" class="form-select" required>
                                <option value="">—</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Количество <span class="text-danger">*</span></label>
                            <input type="number" name="items[0][quantity]" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ставка (опционально)</label>
                            <input type="number" step="0.01" name="items[0][hourly_rate]" class="form-control" placeholder="Оставить пустым для общей ставки">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-success add-item w-100">
                                <i class="bi bi-plus-lg"></i> Добавить
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('admin.rental-requests.index') }}" class="btn btn-secondary">
                        Отмена
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Создать заявку
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let itemIndex = 1;
document.querySelector('.add-item')?.addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const template = document.querySelector('.item-row').cloneNode(true);

    template.querySelectorAll('input, select').forEach(el => {
        const name = el.getAttribute('name');
        if (name) {
            el.setAttribute('name', name.replace(/\[\d+\]/, `[${itemIndex}]`));
            if (el.type !== 'number' || el.name.includes('quantity')) {
                if (el.type === 'number') el.value = '';
            }
        }
    });

    container.appendChild(template);
    itemIndex++;
});
</script>
@endsection
