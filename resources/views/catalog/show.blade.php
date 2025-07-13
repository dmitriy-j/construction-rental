@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <!-- Галерея -->
        <div class="col-md-6">
            <div class="mb-3">
                @if($equipment->images->first())
                    <img src="{{ asset('storage/' . $equipment->images->first()->path) }}"
                         class="img-fluid rounded"
                         style="max-height: 400px; width: 100%; object-fit: cover;">
                @endif
            </div>
            <div class="row">
                @foreach($equipment->images as $image)
                    <div class="col-3 mb-3">
                        <img src="{{ asset('storage/' . $image->path) }}"
                             class="img-thumbnail"
                             style="height: 100px; width: 100%; object-fit: cover;">
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Описание -->
        <div class="col-md-6">
            <h1>{{ $equipment->title }}</h1>
            <p class="text-muted">{{ $equipment->category->name }}</p>

            <div class="d-flex align-items-center mb-3">
                @if($equipment->rentalTerms->isNotEmpty())
                    <span class="h4 mb-0 me-2">{{ $equipment->rentalTerms->first()->price }} ₽/час</span>

                    @if($equipment->availability_status === 'available')
                        <span class="badge bg-success">Доступно</span>
                    @else
                        <span class="badge bg-danger">Недоступно</span>
                    @endif
                @else
                    <span class="text-danger h4">Нет условий аренды</span>
                @endif
            </div>

            <hr>

            <h5>Характеристики</h5>
            <ul class="list-unstyled">
                <li><strong>Бренд:</strong> {{ $equipment->brand ?? 'Не указано' }}</li>
                <li><strong>Модель:</strong> {{ $equipment->model ?? 'Не указано' }}</li>
                <li><strong>Год выпуска:</strong> {{ $equipment->year ?? 'Не указан' }}</li>
                <li><strong>Наработка:</strong> {{ $equipment->hours_worked ?? '0' }} моточасов</li>
            </ul>

            <h5 class="mt-4">Описание</h5>
            <p>{{ $equipment->description }}</p>

            <hr>

            <!-- Форма добавления в корзину -->
            @if($equipment->rentalTerms->isNotEmpty())
                <h5>Условия аренды</h5>
                @foreach($equipment->rentalTerms as $term)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>{{ $term->name }} ({{ $term->price }} ₽/{{ $term->period }})</h6>
                            <p>Минимальный срок: {{ $term->min_rental_period }} {{ $term->period }}</p>

                            @auth
                            <form action="{{ route('cart.add', $term) }}" method="POST" id="rental-form-{{ $term->id }}">
                                @csrf

                                <!-- Блок выбора дат -->
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Начало аренды</label>
                                        <input type="datetime-local" name="start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Окончание аренды</label>
                                        <input type="datetime-local" name="end_date" class="form-control" required>
                                    </div>
                                </div>

                                <!-- Блок доставки -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6>Доставка техники</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="delivery_required_{{ $term->id }}"
                                                   name="delivery_required"
                                                   value="1"
                                                   onchange="toggleDeliveryFields('{{ $term->id }}')">
                                            <label class="form-check-label" for="delivery_required_{{ $term->id }}">
                                                Требуется доставка на объект
                                            </label>
                                        </div>

                                        <div id="deliveryFields_{{ $term->id }}" style="display: none;">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Откуда (база техники)</label>
                                                    <select name="delivery_from_id" class="form-select">
                                                        @if($equipment->company && $equipment->company->locations)
                                                            @foreach($equipment->company->locations as $location)
                                                                <option value="{{ $location->id }}">
                                                                    {{ $location->address }} ({{ $location->city }})
                                                                </option>
                                                            @endforeach
                                                        @else
                                                            <option value="">Локации не найдены</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Куда (стройплощадка)</label>
                                                    <select name="delivery_to_id" class="form-select">
                                                        @if(auth()->user()->company && auth()->user()->company->locations)
                                                            @foreach(auth()->user()->company->locations as $location)
                                                                <option value="{{ $location->id }}">
                                                                    {{ $location->address }} ({{ $location->city }})
                                                                </option>
                                                            @endforeach
                                                        @else
                                                            <option value="">Локации не найдены</option>
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Форма условий аренды -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6>Настройки аренды</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group form-check">
                                        <input type="checkbox"
                                            class="form-check-input use-default-conditions"
                                            id="use_default_conditions_{{ $term->id }}"
                                            name="use_default_conditions"
                                            checked
                                            data-term-id="{{ $term->id }}">
                                        <label class="form-check-label" for="use_default_conditions_{{ $term->id }}">
                                            Использовать условия по умолчанию
                                        </label>

                                        <div class="custom-conditions" id="custom-conditions_{{ $term->id }}">
                                            <div class="form-group">
                                                <label>Тип оплаты:</label>
                                                <select name="payment_type" class="form-control">
                                                    <option value="hourly">Почасовая</option>
                                                    <option value="shift">По сменам</option>
                                                    <option value="daily">Посуточная</option>
                                                    <option value="mileage">За километраж</option>
                                                    <option value="volume">За объем работ</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Часов в смене:</label>
                                                <input type="number" name="shift_hours" value="8" min="1" max="24" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label>Смен в сутки:</label>
                                                <input type="number" name="shifts_per_day" value="1" min="1" max="3" class="form-control">
                                            </div>

                                            <div class="form-group">
                                                <label>Транспортировка:</label>
                                                <select name="transportation" class="form-control">
                                                    <option value="lessor">Организует арендодатель</option>
                                                    <option value="lessee">Организуем самостоятельно</option>
                                                    <option value="shared">Совместная организация</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Оплата ГСМ:</label>
                                                <select name="fuel_responsibility" class="form-control">
                                                    <option value="lessor">Включено в стоимость</option>
                                                    <option value="lessee">Оплачиваем отдельно</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Возможность продления:</label>
                                                <select name="extension_policy" class="form-control">
                                                    <option value="allowed">Разрешено</option>
                                                    <option value="not_allowed">Не разрешено</option>
                                                    <option value="conditional">По согласованию</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Конец формы условий аренды -->

                                <button type="submit" class="btn btn-primary w-100 mt-2">
                                    <i class="bi bi-cart-plus me-1"></i> Добавить в корзину
                                </button>
                            </form>
                            @else
                            <div class="alert alert-warning">
                                <p>Чтобы добавить технику в корзину, пожалуйста, <a href="{{ route('login') }}">войдите в систему</a> или <a href="{{ route('register') }}">зарегистрируйтесь</a>.</p>
                                <p>После авторизации вы сможете настроить условия аренды.</p>
                            </div>
                            @endauth
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded and parsed');

        // Проверка существования элементов
        document.querySelectorAll('.use-default-conditions').forEach(checkbox => {
            const termId = checkbox.dataset.termId;
            console.log(`Checkbox found for term ${termId}`);

            const customConditions = document.getElementById(`custom-conditions_${termId}`);
            console.log(`Custom conditions element:`, customConditions);

            // Принудительное отображение для теста
            customConditions.style.display = 'block';
            setTimeout(() => {
                customConditions.style.display = 'none';
            }, 3000);
        });
    });
</script>
@endsection

<style>
    .custom-conditions {
        display: none;
    }

    .use-default-conditions:not(:checked) ~ .custom-conditions {
        display: block;
    }
</style>
