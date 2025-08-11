@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Редактировать оператора</h1>
        <a href="{{ route('lessor.operators.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('lessor.operators.update', $operator) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="full_name" class="form-label">ФИО</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="{{ $operator->full_name }}" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Телефон</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="{{ $operator->phone }}" required>
                </div>

                <div class="mb-3">
                    <label for="license_number" class="form-label">Номер лицензии</label>
                    <input type="text" class="form-control" id="license_number" name="license_number" value="{{ $operator->license_number }}" required>
                </div>

                <div class="mb-3">
                    <label for="qualification" class="form-label">Квалификация</label>
                    <input type="text" class="form-control" id="qualification" name="qualification" value="{{ $operator->qualification }}">
                </div>

                <div class="mb-3">
                    <label for="equipment_id" class="form-label">Прикрепить к оборудованию</label>
                    <select class="form-select" id="equipment_id" name="equipment_id">
                        <option value="">Не назначено</option>
                        @foreach($equipment as $item)
                            <option value="{{ $item->id }}"
                                {{ $operator->equipment_id == $item->id ? 'selected' : '' }}>
                                {{ $item->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                 <!-- Добавлен выбор смены -->
                <div class="mb-3">
                    <label class="form-label">Тип смены</label>
                    <select class="form-select" name="shift_type" required>
                        <option value="day" {{ $operator->shift_type == 'day' ? 'selected' : '' }}>Дневная</option>
                        <option value="night" {{ $operator->shift_type == 'night' ? 'selected' : '' }}>Ночная</option>
                    </select>
                </div>

                <!-- Поле активности -->
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active"
                            id="is_active" value="1"
                            {{ $operator->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Активен
                        </label>
                    </div>
                </div>

                <!-- Явное поле для неактивного состояния -->
                <input type="hidden" name="is_active_fallback" value="0">



                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div>
</div>
@endsection
