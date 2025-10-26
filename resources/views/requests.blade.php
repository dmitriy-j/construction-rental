@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-5">Активные заявки</h1>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Тип техники</label>
                        <select class="form-select">
                            <option selected>Все</option>
                            <option>Экскаватор</option>
                            <option>Бульдозер</option>
                            <option>Кран</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Статус</label>
                        <select class="form-select">
                            <option>Все</option>
                            <option>Новые</option>
                            <option>В работе</option>
                            <option>Завершённые</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Применить
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица заявок -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Техника</th>
                        <th>Арендатор</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 1; $i <= 5; $i++)
                    <tr>
                        <td>#{{ 1000 + $i }}</td>
                        <td>
                            <i class="bi bi-truck me-2"></i>
                            {{ match($i % 3) { 0 => 'Экскаватор JCB', 1 => 'Бульдозер CAT', 2 => 'Кран Liebherr' } }}
                        </td>
                        <td>ООО "СтройГрад{{ $i }}"</td>
                        <td>{{ now()->subDays($i)->format('d.m.Y') }}</td>
                        <td>
                            <span class="badge {{ match($i % 3) { 
                                0 => 'bg-warning', 
                                1 => 'bg-success', 
                                2 => 'bg-secondary' 
                            } }}">
                                {{ match($i % 3) { 0 => 'Новая', 1 => 'Подтверждена', 2 => 'В работе' } }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    <!-- Пагинация -->
    <div class="mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#">Назад</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Вперед</a>
                </li>
            </ul>
        </nav>
    </div>
</div>
@endsection
