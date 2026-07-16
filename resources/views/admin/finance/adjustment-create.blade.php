@extends('layouts.app')

@section('title', 'Ручная корректировка баланса')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Ручная корректировка баланса</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.finance.balance-adjustments') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history"></i> История корректировок
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.finance.adjustment-store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Компания *</label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                            <option value="">Выберите компанию</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->legal_name }} ({{ $company->is_lessee ? 'Арендатор' : '' }}{{ $company->is_lessor ? 'Арендодатель' : '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Тип операции *</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Начисление (увеличить долг)</option>
                            <option value="debit" {{ old('type') == 'debit' ? 'selected' : '' }}>Списание (уменьшить долг)</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Сумма (₽) *</label>
                        <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" required min="0.01">
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Комментарий *</label>
                        <textarea name="comment" class="form-control @error('comment') is-invalid @enderror"
                                  rows="3" required placeholder="Укажите причину корректировки...">{{ old('comment') }}</textarea>
                        @error('comment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Выполнить корректировку
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
