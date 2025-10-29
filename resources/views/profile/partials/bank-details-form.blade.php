{{-- resources/views/profile/partials/bank-details-form.blade.php --}}
<form method="post" action="{{ url('/profile/bank-details') }}" class="row g-3">
    @csrf
    @method('patch')

    <div class="col-md-6">
        <label for="bank_name" class="form-label">Наименование банка *</label>
        <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
               id="bank_name" name="bank_name"
               value="{{ old('bank_name', $user->company->bank_name ?? '') }}" required>
        @error('bank_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="bik" class="form-label">БИК *</label>
        <input type="text" class="form-control @error('bik') is-invalid @enderror"
               id="bik" name="bik"
               value="{{ old('bik', $user->company->bik ?? '') }}"
               pattern="[0-9]{9}" maxlength="9" required>
        @error('bik')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">9 цифр</div>
    </div>

    <div class="col-md-6">
        <label for="bank_account" class="form-label">Расчетный счет *</label>
        <input type="text" class="form-control @error('bank_account') is-invalid @enderror"
               id="bank_account" name="bank_account"
               value="{{ old('bank_account', $user->company->bank_account ?? '') }}"
               pattern="[0-9]{20}" maxlength="20" required>
        @error('bank_account')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">20 цифр</div>
    </div>

    <div class="col-md-6">
        <label for="correspondent_account" class="form-label">Корреспондентский счет *</label>
        <input type="text" class="form-control @error('correspondent_account') is-invalid @enderror"
               id="correspondent_account" name="correspondent_account"
               value="{{ old('correspondent_account', $user->company->correspondent_account ?? '') }}"
               pattern="[0-9]{20}" maxlength="20" required>
        @error('correspondent_account')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">20 цифр</div>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Сохранить реквизиты
        </button>

        @if (session('success'))
            <span class="text-success ms-3">
                <i class="bi bi-check-lg me-1"></i>{{ session('success') }}
            </span>
        @endif
    </div>
</form>
