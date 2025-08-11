@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Добавить оператора</h1>
        <a href="{{ route('lessor.operators.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('lessor.operators.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="full_name" class="form-label">ФИО</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Телефон</label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>

                <div class="mb-3">
                    <label for="license_number" class="form-label">Номер лицензии</label>
                    <input type="text" class="form-control" id="license_number" name="license_number" required>
                </div>

                <div class="mb-3">
                    <label for="qualification" class="form-label">Квалификация</label>
                    <input type="text" class="form-control" id="qualification" name="qualification">
                </div>

                <div class="mb-3">
                    <label for="equipment_id" class="form-label">Прикрепить к оборудованию</label>
                    <select class="form-select" id="equipment_id" name="equipment_id">
                        <option value="">Не назначено</option>
                        @foreach($equipment as $item)
                            <option value="{{ $item->id }}">{{ $item->title }}</option>
                        @endforeach
                    </select>
                </div>

                 <!-- Добавлен выбор смены -->
                <div class="mb-3">
                    <label for="shift_type" class="form-label">Тип смены</label>
                    <select class="form-select" id="shift_type" name="shift_type" required>
                        <option value="day">Дневная</option>
                        <option value="night">Ночная</option>
                    </select>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                    <label class="form-check-label" for="is_active">Активен</label>
                </div>

                <button type="submit" class="btn btn-primary">Добавить</button>
            </form>
        </div>
    </div>
</div>
@endsection
