@extends('layouts.app')

@section('title', 'Создание заявки на аренду')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">Создание заявки на аренду</h1>
                <a href="{{ route('lessee.rental-requests.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Назад к списку
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <form id="createRentalRequestForm" method="POST"
                          action="{{ route('lessee.rental-requests.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="title" class="form-label">Название заявки *</label>
                                <input type="text" class="form-control" id="title" name="title"
                                       required maxlength="255" placeholder="Например: Аренда экскаватора для земляных работ">
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Подробное описание потребности *</label>
                                <textarea class="form-control" id="description" name="description"
                                          rows="4" required placeholder="Опишите задачи, которые нужно выполнить с помощью техники..."></textarea>
                                <div class="form-text">Минимум 50 символов</div>
                            </div>

                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Категория техники *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Выберите категорию</option>
                                    @foreach($categories as $category)
                                        {{-- Основная категория --}}
                                        <option value="{{ $category->id }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>

                                        {{-- Подкатегории (если есть) --}}
                                        @foreach($category->children as $subcategory)
                                            <option value="{{ $subcategory->id }}"
                                                    {{ old('category_id') == $subcategory->id ? 'selected' : '' }}>
                                                &nbsp;&nbsp;└─ {{ $subcategory->name }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="location_id" class="form-label">Локация выполнения работ *</label>
                                <select class="form-select" id="location_id" name="location_id" required>
                                    <option value="">Выберите локацию</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="rental_period_start" class="form-label">Дата начала аренды *</label>
                                <input type="date" class="form-control" id="rental_period_start"
                                       name="rental_period_start" required min="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="rental_period_end" class="form-label">Дата окончания аренды *</label>
                                <input type="date" class="form-control" id="rental_period_end"
                                       name="rental_period_end" required>
                            </div>

                            <div class="col-md-6">
                                <label for="budget_from" class="form-label">Бюджет от (руб.) *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="budget_from"
                                        name="budget_from" required
                                        pattern="[0-9]*[.,]?[0-9]+"
                                        placeholder="10000"
                                        title="Введите число (например: 10000 или 15000.50)">
                                    <span class="input-group-text">₽</span>
                                </div>
                                <div class="form-text">Только цифры, точка или запятая</div>
                            </div>

                            <div class="col-md-6">
                                <label for="budget_to" class="form-label">Бюджет до (руб.) *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="budget_to"
                                        name="budget_to" required
                                        pattern="[0-9]*[.,]?[0-9]+"
                                        placeholder="20000"
                                        title="Введите число (например: 20000 или 25000.50)">
                                    <span class="input-group-text">₽</span>
                                </div>
                                <div class="form-text">Только цифры, точка или запятая</div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="delivery_required" name="delivery_required">
                                    <label class="form-check-label" for="delivery_required">
                                        Требуется доставка техники к месту работ
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="specifications" class="form-label">Дополнительные требования и характеристики</label>
                                <textarea class="form-control" id="specifications" name="specifications"
                                          rows="3" placeholder="Укажите желаемые характеристики техники, особые требования..."></textarea>
                                <div class="form-text">Например: Требуется техника с оператором, наличие сертификатов и т.д.</div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                    <i class="fas fa-plus me-2"></i>Создать заявку
                                </button>
                                <a href="{{ route('lessee.rental-requests.index') }}" class="btn btn-outline-secondary">
                                    Отмена
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Подсказки --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="fas fa-lightbulb me-2"></i>Советы по заполнению</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>📝 Как правильно оформить заявку:</h6>
                        <ul class="mb-0">
                            <li>Указывайте реалистичный бюджет</li>
                            <li>Подробно опишите задачи для техники</li>
                            <li>Укажите точные даты аренды</li>
                            <li>Четко сформулируйте требования</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Преимущества --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="fas fa-star me-2"></i>Преимущества системы заявок</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bolt text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Быстрые предложения</h6>
                            <p class="text-muted mb-0">Получайте предложения от проверенных арендодателей</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Лучшие цены</h6>
                            <p class="text-muted mb-0">Сравнивайте предложения и выбирайте оптимальные</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-shield-alt text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Безопасная сделка</h6>
                            <p class="text-muted mb-0">Все сделки защищены платформой</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createRentalRequestForm');
    const submitBtn = document.getElementById('submitBtn');

    // Функция для нормализации чисел перед отправкой
    function normalizeNumber(value) {
        if (!value) return '0';

        // Заменяем запятую на точку и убираем пробелы
        return value.toString()
            .replace(/\s/g, '')
            .replace(/,/g, '.')
            .replace(/[^\d.-]/g, '');
    }

    // Валидация дат
    const startDateInput = document.getElementById('rental_period_start');
    const endDateInput = document.getElementById('rental_period_end');

    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = '';
        }
    });

    // Валидация бюджета
    const budgetFrom = document.getElementById('budget_from');
    const budgetTo = document.getElementById('budget_to');

    budgetFrom.addEventListener('blur', function() {
        let value = normalizeNumber(this.value);
        this.value = value;

        if (budgetTo.value) {
            let toValue = normalizeNumber(budgetTo.value);
            if (parseFloat(toValue) < parseFloat(value)) {
                budgetTo.value = (parseFloat(value) + 1000).toString();
            }
        }
    });

    // Отправка формы с предварительной обработкой данных
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Нормализуем числовые поля перед отправкой
        budgetFrom.value = normalizeNumber(budgetFrom.value);
        budgetTo.value = normalizeNumber(budgetTo.value);

        // Валидация на клиенте
        const fromValue = parseFloat(budgetFrom.value);
        const toValue = parseFloat(budgetTo.value);

        if (isNaN(fromValue) || isNaN(toValue)) {
            alert('Пожалуйста, введите корректные числовые значения для бюджета');
            return;
        }

        if (fromValue >= toValue) {
            alert('Бюджет "до" должен быть больше бюджета "от"');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Создание...';

        // Создаем FormData и логируем данные для отладки
        const formData = new FormData(this);
        console.log('Отправляемые данные:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value + ' (тип: ' + typeof value + ')');
        }

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                let errorMessage = 'Ошибка: ' + data.message;
                if (data.errors) {
                    errorMessage += '\n' + JSON.stringify(data.errors, null, 2);
                }
                alert(errorMessage);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Создать заявку';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при создании заявки: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Создать заявку';
        });
    });
});
</script>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const originalOptions = categorySelect.innerHTML;

    // Функция для фильтрации подкатегорий при поиске
    categorySelect.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();

        if (searchTerm.length > 2) {
            // Показываем только соответствующие категории
            const options = categorySelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value === '') return;
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        } else {
            // Восстанавливаем все опции
            const options = categorySelect.querySelectorAll('option');
            options.forEach(option => option.style.display = '');
        }
    });
});
</script>
@endpush
