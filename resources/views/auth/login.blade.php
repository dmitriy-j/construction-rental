@extends('layouts.auth')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf

    <!-- Session Status -->
    @if(session('status'))
    <div class="alert alert-success auth-form-group">
        <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
    </div>
    @endif

    <!-- Email -->
    <div class="auth-form-group">
        <label for="email" class="form-label small mb-1">Рабочий Email</label>
        <div class="auth-input-group">
            <i class="bi bi-envelope auth-input-icon"></i>
            <input id="email" type="email"
                class="auth-input @error('email') is-invalid @enderror"
                name="email" value="{{ old('email') }}"
                required autofocus placeholder="ваш@email.com">
        </div>
        @error('email')
            <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="auth-form-group">
        <label for="password" class="form-label small mb-1">Пароль</label>
        <div class="auth-input-group">
            <i class="bi bi-lock auth-input-icon"></i>
            <input id="password" type="password"
                class="auth-input @error('password') is-invalid @enderror"
                name="password" required placeholder="••••••••">
        </div>
        @error('password')
            <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
        @enderror
    </div>

    <!-- Remember Me & Forgot Password -->
    <div class="d-flex justify-content-between align-items-center auth-form-group">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label small" for="remember">
                Запомнить меня
            </label>
        </div>
        <a href="{{ route('password.request') }}" class="small text-decoration-none">
            Забыли пароль?
        </a>
    </div>

    <!-- Submit Button -->
    <div class="auth-form-group">
        <button type="submit" class="auth-btn">
            <i class="bi bi-box-arrow-in-right"></i> Войти в систему
        </button>
    </div>

    <!-- Register Link -->
    <div class="text-center mt-4 pt-3">
        <p class="small mb-3">Ещё нет аккаунта?</p>
        <a href="{{ route('register') }}" class="btn btn-outline-primary w-100">
            <i class="bi bi-person-plus me-1"></i> Создать новый аккаунт
        </a>
    </div>
</form>
@endsection
