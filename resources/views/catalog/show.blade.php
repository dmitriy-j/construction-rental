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
                    <span class="h4 mb-0 me-2">
                        {{ $equipment->display_price }}
                    </span>

                    @php $status = $equipment->status_details; @endphp
                    <span class="badge bg-{{ $status['class'] }}">
                        {{ $status['message'] }}
                    </span>
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

            <!-- График доступности -->
            @if($equipment->future_availability->isNotEmpty())
                <h5>График доступности</h5>
                <div class="availability-calendar mb-4">
                    <div class="d-flex flex-wrap">
                        @foreach($equipment->future_availability as $day)
                            @php
                                $statusClass = match($day->status) {
                                    'available' => 'bg-success',
                                    'booked' => 'bg-danger',
                                    'maintenance' => 'bg-secondary',
                                    default => 'bg-light'
                                };
                            @endphp
                            <div class="day p-2 border rounded m-1 text-center {{ $statusClass }}"
                                 style="width: 40px; height: 40px; font-size: 0.8rem;"
                                 title="{{ $day->date->format('d.m.Y') }}: {{ ucfirst($day->status) }}">
                                {{ $day->date->format('d.m') }}
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2">
                        <span class="badge bg-success me-1"></span> Доступно
                        <span class="badge bg-danger mx-1"></span> Занято
                        <span class="badge bg-secondary mx-1"></span> Обслуживание
                    </small>
                </div>
            @endif

            <!-- Форма добавления в корзину -->
            @if($equipment->rentalTerms->isNotEmpty())
                <h5>Условия аренды</h5>
                @foreach($equipment->rentalTerms as $term)
                <div class="card mb-3">
                    <div class="card-body">
                        @php
                            $displayPrice = $term->price_per_hour;

                            if (auth()->check() && $company = auth()->user()->company) {
                                $pricingService = app(\App\Services\PricingService::class);
                                $markup = $pricingService->getPlatformMarkup(
                                    $equipment,
                                    $company,
                                    1 // 1 час для расчета
                                );
                                $displayPrice += $pricingService->applyMarkup($term->price_per_hour, $markup);
                                $displayPrice = number_format($displayPrice, 2) . ' ₽/час';
                            } else {
                                $displayPrice = number_format($term->price_per_hour, 2) . ' ₽/час';
                            }
                        @endphp

                        <h6>Почасовая аренда ({{ $displayPrice }})</h6>
                        <p>Минимальный срок: {{ $term->min_rental_hours }} часов</p>

                        @auth
                        <form action="{{ route('cart.add', $term) }}" method="POST" id="rental-form-{{ $term->id }}">
                            @csrf

                            <!-- Блок выбора дат -->
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Начало аренды</label>
                                    <input type="datetime-local" name="start_date" class="form-control" required
                                        value="{{ $defaultStart->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Окончание аренды</label>
                                    <input type="datetime-local" name="end_date" class="form-control" required
                                        value="{{ $defaultEnd->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>

                            <!-- Блок доставки -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6>Доставка техники</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input type="hidden" name="delivery_required" value="0">
                                        <input class="form-check-input delivery-toggle"
                                               type="checkbox"
                                               id="delivery_required_{{ $term->id }}"
                                               name="delivery_required"
                                               value="1">
                                        <label class="form-check-label" for="delivery_required_{{ $term->id }}">
                                            Требуется доставка на объект
                                        </label>
                                    </div>

                                    <div id="deliveryFields_{{ $term->id }}" style="display: none;">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Откуда (база техники)</label>
                                                <select name="delivery_from_id" class="form-select">
                                                    @foreach($equipment->company->locations as $location)
                                                        <option value="{{ $location->id }}">
                                                            {{ $location->short_address }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Куда (строительный объект)</label>
                                                <select name="delivery_location_id" class="form-select">
                                                    @foreach(auth()->user()->company->activeRentalConditions as $condition)
                                                        @if($condition->deliveryLocation)
                                                            <option value="{{ $condition->delivery_location_id }}">
                                                                {{ $condition->deliveryLocation->short_address }}
                                                                (Договор #{{ $condition->contract->id }})
                                                            </option>
                                                        @endif
                                                    @endforeach
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
                                            checked>
                                        <label class="form-check-label" for="use_default_conditions_{{ $term->id }}">
                                            Использовать условия по умолчанию
                                        </label>
                                    </div>

                                    <div class="custom-conditions" id="custom-conditions_{{ $term->id }}" style="display: none;">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Тип оплаты:</label>
                                            <select name="payment_type" class="form-select">
                                                <option value="hourly">Почасовая</option>
                                                <option value="shift">По сменам</option>
                                                <option value="daily">Посуточная</option>
                                                <option value="mileage">За километраж</option>
                                                <option value="volume">За объем работ</option>
                                            </select>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Часов в смене:</label>
                                                <input type="number" name="shift_hours" value="8" min="1" max="24" class="form-control">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Смен в сутки:</label>
                                                <input type="number" name="shifts_per_day" value="1" min="1" max="3" class="form-control">
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Транспортировка:</label>
                                                <select name="transportation" class="form-select">
                                                    <option value="lessor">Организует арендодатель</option>
                                                    <option value="lessee">Организуем самостоятельно</option>
                                                    <option value="shared">Совместная организация</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Оплата ГСМ:</label>
                                                <select name="fuel_responsibility" class="form-select">
                                                    <option value="lessor">Включено в стоимость</option>
                                                    <option value="lessee">Оплачиваем отдельно</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Возможность продления:</label>
                                            <select name="extension_policy" class="form-select">
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

@push('scripts')
  @vite(['resources/js/catalog/show.js'])
@endpush

<style>
.custom-conditions, .delivery-fields {
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}
</style>
