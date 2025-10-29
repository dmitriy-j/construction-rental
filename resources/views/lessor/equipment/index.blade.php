@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Моя техника</h1>
        <div>
            <a href="{{ route('lessor.equipment.mass-import.create') }}" class="btn btn-info me-2">
                <i class="bi bi-upload"></i> Массовая загрузка
            </a>
            <a href="{{ route('lessor.equipment.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Добавить технику
            </a>
        </div>
    </div>

    @if($equipments->isEmpty())
        <div class="alert alert-info">
            У вас пока нет добавленной техники. Начните с добавления первого оборудования.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Бренд/Модель</th>
                        <th>Год</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($equipments as $equipment)
                    <tr
                        class="clickable-row"
                        data-url="{{ route('lessor.equipment.show', $equipment) }}"
                    >
                        <td>{{ $equipment->title }}</td>
                        <td>{{ $equipment->category->name }}</td>
                        <td>{{ $equipment->brand }} {{ $equipment->model }}</td>
                        <td>{{ $equipment->year }}</td>
                        <td>
                            @if($equipment->is_approved)
                                <span class="badge bg-success">Одобрено</span>
                            @else
                                <span class="badge bg-warning">На модерации</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('lessor.equipment.edit', $equipment) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('lessor.equipment.destroy', $equipment) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Удалить эту технику?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<style>
    .clickable-row {
        cursor: pointer;
    }
    .clickable-row:hover {
        background-color: #f8f9fa;
    }
    .actions-cell {
        cursor: default;
    }
    .actions-cell a,
    .actions-cell form,
    .actions-cell button {
        cursor: pointer;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.clickable-row');

        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Проверяем, был ли клик на элементе внутри actions-cell
                if (e.target.closest('.actions-cell')) {
                    return;
                }

                window.location.href = row.dataset.url;
            });
        });
    });
</script>
@endsection
