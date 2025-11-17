@extends('layouts.app')

@section('title', 'Управление наценками платформы')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Система наценок платформы</h5>
                    <div>
                        <a href="{{ route('markups.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Добавить наценку
                        </a>
                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#testModal">
                            <i class="bi bi-calculator"></i> Тест расчета
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Тип сущности</label>
                    <select name="markupable_type" class="form-select">
                        <option value="">Все типы</option>
                        @foreach($markupableTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('markupable_type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Контекст</label>
                    <select name="entity_type" class="form-select">
                        <option value="">Все контексты</option>
                        @foreach($entityTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('entity_type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Тип наценки</label>
                    <select name="type" class="form-select">
                        <option value="">Все типы</option>
                        @foreach($markupTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">Применить</button>
                        <a href="{{ route('markups.index') }}" class="btn btn-outline-secondary">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица наценок -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Список наценок</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Применение</th>
                            <th>Контекст</th>
                            <th>Тип</th>
                            <th>Значение</th>
                            <th>Статус</th>
                            <th>Период действия</th>
                            <th>Создана</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($markups as $markup)
                        <tr>
                            <td>{{ $markup->id }}</td>
                            <td>
                                @if($markup->markupable)
                                    <span class="badge bg-info">
                                        {{ $markupableTypes[$markup->markupable_type] ?? $markup->markupable_type }}:
                                        @if($markup->markupable_type === 'App\Models\Equipment')
                                            {{ $markup->markupable->title ?? 'N/A' }}
                                        @elseif($markup->markupable_type === 'App\Models\Category')
                                            {{ $markup->markupable->name ?? 'N/A' }}
                                        @elseif($markup->markupable_type === 'App\Models\Company')
                                            {{ $markup->markupable->legal_name ?? $markup->markupable->name ?? 'N/A' }}
                                        @else
                                            {{ $markup->markupable->name ?? $markup->markupable->title ?? 'N/A' }}
                                        @endif
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Общая наценка</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ e($entityTypes[$markup->entity_type] ?? $markup->entity_type) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark">{{ e($markupTypes[$markup->type] ?? $markup->type) }}</span>
                            </td>
                            <td>
                                <strong>{{ number_format($markup->value, 2) }}</strong>
                                @if($markup->type === 'percent')% @else ₽/час @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $markup->is_active ? 'success' : 'danger' }}">
                                    {{ $markup->is_active ? 'Активна' : 'Неактивна' }}
                                </span>
                            </td>
                            <td>
                                @if($markup->valid_from || $markup->valid_to)
                                    <small>
                                        {{ $markup->valid_from ? $markup->valid_from->format('d.m.Y') : '∞' }} -
                                        {{ $markup->valid_to ? $markup->valid_to->format('d.m.Y') : '∞' }}
                                    </small>
                                @else
                                    <span class="text-muted">Постоянно</span>
                                @endif
                            </td>
                            <td>{{ $markup->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('markups.edit', $markup) }}"
                                       class="btn btn-outline-primary" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger"
                                            onclick="confirmDelete({{ $markup->id }})" title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $markup->id }}"
                                          action="{{ route('markups.destroy', $markup) }}"
                                          method="POST" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2">Наценки не найдены</p>
                                <a href="{{ route('markups.create') }}" class="btn btn-primary btn-sm">
                                    Создать первую наценку
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <div class="mt-3">
                {{ $markups->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
<!-- Модальное окно тестирования -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Тестирование расчета наценки</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testCalculationForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Базовая цена (₽/час) *</label>
                            <input type="number" name="base_price" class="form-control" value="1000" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Рабочие часы *</label>
                            <input type="number" name="working_hours" class="form-control" value="8" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Контекст *</label>
                            <select name="entity_type" class="form-select" required>
                                @foreach($entityTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID оборудования (опционально)</label>
                            <input type="number" name="equipment_id" class="form-control" placeholder="Например, 123">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID категории (опционально)</label>
                            <input type="number" name="category_id" class="form-control" placeholder="Например, 45">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID компании (опционально)</label>
                            <input type="number" name="company_id" class="form-control" placeholder="Например, 67">
                        </div>
                    </div>
                </form>

                <!-- КОНТЕЙНЕР РЕЗУЛЬТАТОВ - ГАРАНТИРОВАННОЕ НАЛИЧИЕ -->
                <div id="testResult" class="mt-4" style="display: none;">
                    <div class="alert alert-info m-0">
                        <h6 class="alert-heading mb-2">Результат расчета:</h6>
                        <div id="resultContent" class="calculation-content">
                            <!-- Сюда будут загружаться результаты -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary" onclick="testCalculation()" id="calculateTestBtn">
                    <i class="bi bi-calculator me-1"></i> Рассчитать
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function testCalculation() {
    console.log('🔧 testCalculation function started');

    // УСИЛЕННЫЕ ЗАЩИТНЫЕ ПРОВЕРКИ ДОМ ЭЛЕМЕНТОВ
    const form = document.getElementById('testCalculationForm');
    const resultDiv = document.getElementById('testResult');
    const contentDiv = document.getElementById('resultContent');
    const submitBtn = document.querySelector('#testModal .btn-primary');

    // Детальная проверка каждого элемента
    if (!form) {
        console.error('❌ Form not found: #testCalculationForm');
        console.log('Available forms:', document.querySelectorAll('form').length);
        alert('Ошибка: форма тестирования не найдена. Обновите страницу.');
        return;
    }

    if (!resultDiv) {
        console.error('❌ Result div not found: #testResult');
        console.log('Available divs with testResult:', document.querySelectorAll('#testResult').length);

        // Попробуем найти альтернативные селекторы
        const alternativeResult = document.querySelector('[id*="testResult"], [class*="test-result"]');
        if (alternativeResult) {
            console.log('Found alternative result container:', alternativeResult);
        }

        alert('Ошибка: контейнер результатов не найден. Обновите страницу.');
        return;
    }

    if (!contentDiv) {
        console.error('❌ Content div not found: #resultContent');
        console.log('Available divs with resultContent:', document.querySelectorAll('#resultContent').length);

        // Попробуем создать элемент, если его нет
        console.log('🔄 Attempting to create missing resultContent element...');
        const newContentDiv = document.createElement('div');
        newContentDiv.id = 'resultContent';
        newContentDiv.className = 'calculation-content';

        if (resultDiv) {
            const alertDiv = resultDiv.querySelector('.alert');
            if (alertDiv) {
                alertDiv.appendChild(newContentDiv);
                console.log('✅ Created resultContent element dynamically');
                // Перезаписываем переменную
                contentDiv = newContentDiv;
            } else {
                resultDiv.appendChild(newContentDiv);
                console.log('✅ Created resultContent element in resultDiv');
                contentDiv = newContentDiv;
            }
        } else {
            alert('Ошибка: не удалось создать контейнер результатов. Обновите страницу.');
            return;
        }
    }

    if (!submitBtn) {
        console.error('❌ Submit button not found in #testModal');
        const modalButtons = document.querySelectorAll('#testModal button');
        console.log('Available buttons in modal:', modalButtons);
        alert('Ошибка: кнопка расчета не найдена. Обновите страницу.');
        return;
    }

    console.log('✅ All DOM elements verified:', {
        form: form !== null,
        resultDiv: resultDiv !== null,
        contentDiv: contentDiv !== null,
        submitBtn: submitBtn !== null
    });

    // Показываем индикатор загрузки
    resultDiv.style.display = 'block';
    contentDiv.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2 text-muted">Выполняется расчет наценки...</div>
        </div>
    `;

    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Расчет...';
    submitBtn.disabled = true;

    // Собираем данные формы
    const formData = new FormData(form);

    // Добавляем логирование данных
    const formDataObj = {};
    for (let [key, value] of formData.entries()) {
        formDataObj[key] = value;
    }
    console.log('📤 Sending form data:', formDataObj);

    // Выполняем запрос
    fetch('{{ route("markups.test-calculation") }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        console.log('📡 Response received, status:', response.status, response.statusText);

        if (!response.ok) {
            return response.text().then(text => {
                console.error('❌ Server error response:', text);
                let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                try {
                    const errorData = JSON.parse(text);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    // Не JSON ответ, используем текст как есть
                    if (text && text.length < 100) {
                        errorMessage = text;
                    }
                }
                throw new Error(errorMessage);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('📊 Response data parsed:', data);

        if (data.success && data.result) {
            const result = data.result;
            // Используем безопасное обращение к свойствам
            const calculationDetails = result.calculation_details || {};

            contentDiv.innerHTML = `
                <div class="calculation-result">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <tr>
                                <td class="fw-bold" style="width: 40%">Базовая цена:</td>
                                <td class="text-end">${formatCurrency(result.base_price)}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Тип наценки:</td>
                                <td class="text-end">
                                    <span class="badge bg-warning text-dark">${escapeHtml(result.markup_type || 'Не указан')}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Значение наценки:</td>
                                <td class="text-end">${escapeHtml(result.markup_value || '0')}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Сумма наценки:</td>
                                <td class="text-end fw-bold text-primary">${formatCurrency(result.markup_amount || 0)}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Итоговая цена:</td>
                                <td class="text-end fw-bold text-success">${formatCurrency(result.final_price || result.base_price)}</td>
                            </tr>
                            <tr>
                                <td>Рабочие часы:</td>
                                <td class="text-end">${escapeHtml(result.working_hours || '0')} ч</td>
                            </tr>
                            <tr>
                                <td>Источник:</td>
                                <td class="text-end">
                                    <small class="text-muted">${escapeHtml(calculationDetails.source || 'Не указан')}</small>
                                </td>
                            </tr>
                            <tr>
                                <td>Приоритет:</td>
                                <td class="text-end">${escapeHtml(calculationDetails.priority || '0')}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Визуализация расчета -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">Визуализация расчета:</h6>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Базовая цена:</span>
                            <span class="fw-bold">${formatCurrency(result.base_price)}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Наценка:</span>
                            <span class="fw-bold text-primary">+ ${formatCurrency(result.markup_amount || 0)}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Итоговая цена:</span>
                            <span class="fw-bold text-success fs-5">${formatCurrency(result.final_price || result.base_price)}</span>
                        </div>
                    </div>
                </div>
            `;
        } else {
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6 class="alert-heading">Ошибка расчета:</h6>
                    <p class="mb-0">${escapeHtml(data.message || 'Неизвестная ошибка сервера')}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('💥 Fetch error:', error);
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6 class="alert-heading">Ошибка соединения:</h6>
                <p class="mb-0">${escapeHtml(error.message)}</p>
                <small class="text-muted">Проверьте консоль браузера (F12) для подробной информации</small>
            </div>
        `;
    })
    .finally(() => {
        console.log('✅ Calculation process completed');
        // Восстанавливаем кнопку
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;

        // Плавно прокручиваем к результатам
        setTimeout(() => {
            if (resultDiv) {
                resultDiv.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'nearest'
                });
            }
        }, 100);
    });
}

// Вспомогательные функции
function formatCurrency(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) return '0,00 ₽';
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 2
    }).format(amount);
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function confirmDelete(markupId) {
    if (confirm('Вы уверены, что хотите удалить эту наценку? Это действие нельзя отменить.')) {
        showLoadingState('delete-' + markupId);
        document.getElementById('delete-form-' + markupId).submit();
    }
}

function showLoadingState(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        element.disabled = true;
    }
}

// УСИЛЕННАЯ ИНИЦИАЛИЗАЦИЯ МОДАЛЬНОГО ОКНА
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Initializing enhanced test modal handlers...');

    const testModal = document.getElementById('testModal');

    if (testModal) {
        console.log('✅ Test modal found, attaching enhanced event listeners...');

        // Гарантируем наличие всех необходимых элементов
        const ensureModalElements = () => {
            const resultDiv = document.getElementById('testResult');
            const contentDiv = document.getElementById('resultContent');

            if (!contentDiv && resultDiv) {
                console.log('🔄 Creating missing resultContent element...');
                const newContentDiv = document.createElement('div');
                newContentDiv.id = 'resultContent';
                newContentDiv.className = 'calculation-content';

                const alertDiv = resultDiv.querySelector('.alert');
                if (alertDiv) {
                    const heading = alertDiv.querySelector('h6');
                    if (heading) {
                        heading.insertAdjacentElement('afterend', newContentDiv);
                    } else {
                        alertDiv.appendChild(newContentDiv);
                    }
                } else {
                    resultDiv.appendChild(newContentDiv);
                }
                console.log('✅ resultContent element created');
            }
        };

        // Сброс состояния при открытии модального окна
        testModal.addEventListener('show.bs.modal', function() {
            console.log('📱 Test modal opening...');
            ensureModalElements();

            const resultDiv = document.getElementById('testResult');
            const contentDiv = document.getElementById('resultContent');

            if (resultDiv) {
                resultDiv.style.display = 'none';
                console.log('✅ Result div hidden');
            }
            if (contentDiv) {
                contentDiv.innerHTML = '';
                console.log('✅ Content div cleared');
            }
        });

        // Сброс состояния при закрытии модального окна
        testModal.addEventListener('hidden.bs.modal', function() {
            console.log('📱 Test modal closing...');
            const resultDiv = document.getElementById('testResult');
            const contentDiv = document.getElementById('resultContent');

            if (resultDiv) {
                resultDiv.style.display = 'none';
                console.log('✅ Result div hidden');
            }
            if (contentDiv) {
                contentDiv.innerHTML = '';
                console.log('✅ Content div cleared');
            }
        });

        // Гарантируем элементы при первой загрузке
        ensureModalElements();
        console.log('✅ Enhanced test modal initialization completed');
    } else {
        console.error('❌ Test modal not found: #testModal');

        // Попробуем найти модальное окно другими способами
        const modals = document.querySelectorAll('.modal');
        console.log('Available modals:', modals.length);
        modals.forEach((modal, index) => {
            console.log(`Modal ${index}:`, modal.id, modal.className);
        });
    }

    // Автоматически скрываем алерты через 5 секунд
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            try {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch (e) {
                console.log('Alert auto-close:', e);
            }
        });
    }, 5000);

    console.log('✅ DOM initialization completed');
});

// ЭКСТРЕННАЯ ДИАГНОСТИКА
function debugModalElements() {
    console.log('=== 🐛 ENHANCED MODAL DEBUG ===');
    console.log('testModal:', document.getElementById('testModal'));
    console.log('testCalculationForm:', document.getElementById('testCalculationForm'));
    console.log('testResult:', document.getElementById('testResult'));
    console.log('resultContent:', document.getElementById('resultContent'));

    const submitBtn = document.querySelector('#testModal .btn-primary');
    console.log('submitBtn:', submitBtn);

    // Проверим всю структуру модального окна
    const modal = document.getElementById('testModal');
    if (modal) {
        console.log('Modal structure:', modal.innerHTML);
    }
    console.log('============================');
}

// Запуск диагностики при загрузке
setTimeout(debugModalElements, 1000);
</script>
@endpush
