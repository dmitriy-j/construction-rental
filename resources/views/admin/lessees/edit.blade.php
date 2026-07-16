@extends('layouts.app')

@section('title', 'Редактирование арендатора')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Редактирование: {{ $lessee->legal_name }}</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.lessees.show', $lessee) }}" class="btn btn-outline-info">
                <i class="bi bi-eye"></i> Просмотр
            </a>
            <a href="{{ route('admin.lessees.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.lessees.update', $lessee) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <!-- Основная информация -->
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2">Основная информация</h5>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Юридическое название *</label>
                        <input type="text" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror"
                               value="{{ old('legal_name', $lessee->legal_name) }}" required>
                        @error('legal_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Тип организации</label>
                        <select name="legal_type" class="form-select">
                            <option value="ooo" {{ old('legal_type', $lessee->legal_type) == 'ooo' ? 'selected' : '' }}>ООО</option>
                            <option value="ip" {{ old('legal_type', $lessee->legal_type) == 'ip' ? 'selected' : '' }}>ИП</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Директор / ИП</label>
                        <input type="text" name="director_name" class="form-control"
                               value="{{ old('director_name', $lessee->director_name) }}">
                    </div>

                    <!-- Реквизиты -->
                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Реквизиты</h5>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ИНН *</label>
                        <input type="text" name="inn" class="form-control @error('inn') is-invalid @enderror"
                               value="{{ old('inn', $lessee->inn) }}" required>
                        @error('inn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">КПП</label>
                        <input type="text" name="kpp" class="form-control"
                               value="{{ old('kpp', $lessee->kpp) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ОГРН</label>
                        <input type="text" name="ogrn" class="form-control"
                               value="{{ old('ogrn', $lessee->ogrn) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ОКПО</label>
                        <input type="text" name="okpo" class="form-control"
                               value="{{ old('okpo', $lessee->okpo) }}">
                    </div>

                    <!-- Адреса -->
                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Адреса</h5>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Юридический адрес</label>
                        <textarea name="legal_address" class="form-control" rows="2">{{ old('legal_address', $lessee->legal_address) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Фактический адрес</label>
                        <textarea name="actual_address" class="form-control" rows="2">{{ old('actual_address', $lessee->actual_address) }}</textarea>
                    </div>

                    <!-- Контакты -->
                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Контакты</h5>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Телефон</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $lessee->phone) }}">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Контакты (дополнительно)</label>
                        <input type="text" name="contacts" class="form-control"
                               value="{{ old('contacts', $lessee->contacts) }}">
                    </div>

                    <!-- Банковские реквизиты -->
                    <div class="col-md-12 mt-4">
                        <h5 class="border-bottom pb-2">Банковские реквизиты</h5>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Название банка</label>
                        <input type="text" name="bank_name" class="form-control"
                               value="{{ old('bank_name', $lessee->bank_name) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Расчётный счёт</label>
                        <input type="text" name="bank_account" class="form-control"
                               value="{{ old('bank_account', $lessee->bank_account) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">БИК</label>
                        <input type="text" name="bik" class="form-control"
                               value="{{ old('bik', $lessee->bik) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Корр. счёт</label>
                        <input type="text" name="correspondent_account" class="form-control"
                               value="{{ old('correspondent_account', $lessee->correspondent_account) }}">
                    </div>

                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Сохранить изменения
                        </button>
                        <a href="{{ route('admin.lessees.index') }}" class="btn btn-outline-secondary">Отмена</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
