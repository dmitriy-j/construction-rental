{{-- resources/views/profile/partials/update-password-form.blade.php --}}
<form method="post" action="{{ route('password.update') }}" class="row g-3">
    @csrf
    @method('put')

    <header class="col-12">
        <h5 class="mb-2">
            <i class="bi bi-shield-lock me-2"></i>Смена пароля
        </h5>
        <p class="text-muted small mb-3">
            Используйте длинный сложный пароль для обеспечения безопасности вашего аккаунта.
        </p>
    </header>

    <div class="col-12">
        <label for="update_password_current_password" class="form-label">Текущий пароль</label>
        <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
               id="update_password_current_password" name="current_password"
               autocomplete="current-password" required>
        @error('current_password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="update_password_password" class="form-label">Новый пароль</label>
        <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror"
               id="update_password_password" name="password"
               autocomplete="new-password" required>
        @error('password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Минимум 8 символов, рекомендуем использовать буквы, цифры и специальные символы</div>
    </div>

    <div class="col-12">
        <label for="update_password_password_confirmation" class="form-label">Подтверждение нового пароля</label>
        <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
               id="update_password_password_confirmation" name="password_confirmation"
               autocomplete="new-password" required>
        @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Сохранить пароль
        </button>

        @if (session('status') === 'password-updated')
            <span class="text-success ms-3">
                <i class="bi bi-check-lg me-1"></i>Пароль успешно изменен
            </span>
        @endif
    </div>
</form>
