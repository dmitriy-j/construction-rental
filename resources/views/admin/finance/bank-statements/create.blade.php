@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Загрузка банковской выписки</h3>
    </div>
    <div class="card-body">
        {{-- Изменяем маршрут с process на store --}}
        <form action="{{ route('admin.bank-statements.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="bank_name">Название банка</label>
                <input type="text" name="bank_name" id="bank_name"
                       class="form-control @error('bank_name') is-invalid @enderror"
                       value="{{ old('bank_name') }}" required>
                @error('bank_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="statement">Файл выписки (.txt)</label>
                <input type="file" name="statement" id="statement"
                       class="form-control-file @error('statement') is-invalid @enderror"
                       accept=".txt" required>
                @error('statement')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    Поддерживается формат 1CClientBankExchange
                </small>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Загрузить и обработать
            </button>

            <a href="{{ route('admin.bank-statements.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </form>
    </div>
</div>
@endsection
