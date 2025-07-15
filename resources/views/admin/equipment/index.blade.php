@extends('layouts.app')

@section('title', 'Каталог техники')

@section('content')
<div class="container-fluid">
    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Поиск..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="company_id" class="form-select">
                        <option value="">Все компании</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category_id" class="form-select">
                        <option value="">Все категории</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="is_approved" class="form-select">
                        <option value="">Все статусы</option>
                        <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>Одобрено</option>
                        <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>На проверке</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Применить</button>
                    <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список техники</h5>
            <span>Всего: {{ $equipment->total() }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Бренд</th>
                            <th>Модель</th>
                            <th>Год</th>
                            <th>Компания</th>
                            <th>Категория</th>
                            <th>Локация</th>
                            <th>Часы</th>
                            <th>Цены аренды</th>
                            <th>Статус</th>
                            <th>Дата добавления</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipment as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->title }}</td>
                            <td>{{ $item->brand }}</td>
                            <td>{{ $item->model }}</td>
                            <td>{{ $item->year }}</td>
                            <td>{{ $item->company->name }}</td>
                            <td>{{ $item->category->name }}</td>
                            <td>{{ $item->location->name }}</td>
                            <td>{{ number_format($item->hours_worked, 2) }} </td>
                            <td class="text-nowrap">
    @php
        // Получаем условия аренды
        $terms = $item->rentalTerms->first() ?: new App\Models\EquipmentRentalTerm();
    @endphp

    @if($terms->price_per_hour)
        <div class="d-flex justify-content-between">
            
            <span>{{ number_format($terms->price_per_hour, 2) }}</span>
        </div>
    @endif
</td>
                            <td>
                                <span class="badge bg-{{ $item->is_approved ? 'success' : 'warning' }}">
                                    {{ $item->is_approved ? 'Одобрено' : 'На проверке' }}
                                </span>
                            </td>
                            <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-nowrap">
                                @if(!$item->is_approved)
                                    <a href="{{ route('admin.equipment.approve', $item) }}" 
                                       class="btn btn-sm btn-success"
                                       title="Одобрить">
                                        <i class="bi bi-check-lg"></i>
                                    </a>
                                @else
                                    <a href="{{ route('admin.equipment.reject', $item) }}" 
                                       class="btn btn-sm btn-warning"
                                       title="Отклонить">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                @endif
                                <a href="#" 
                                   class="btn btn-sm btn-info"
                                   title="Подробнее">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <div class="mt-3">
                {{ $equipment->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table th {
        white-space: nowrap;
        position: relative;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
</style>
@endsection