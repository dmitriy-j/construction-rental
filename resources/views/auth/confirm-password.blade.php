@extends('layouts.auth')

@section('title', 'Подтверждение пароля')
@section('page-title', 'Подтверждение пароля')
@section('background-text', 'Пожалуйста, подтвердите ваш пароль для продолжения.')

@section('content')
<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="auth-form-group mb-4">
        <p class="text-center">
            <i class="bi bi-shield-check fs-2 text-primary mb-3 d-block"></i>
            Это защищенная зона. Пожалуйста, подтвердите ваш пароль для продолжения.
        </p>
    </div>

    <!-- Password -->
    <div class="auth-form-group">
        <label for="password" class="form-label small mb-1">Ваш пароль</label>
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

    <!-- Submit Button -->
    <div class="auth-form-group">
        <button type="submit" class="auth-btn">
            <i class="bi bi-check-circle"></i> Подтвердить
        </button>
    </div>

    <!-- Forgot Password Link -->
    <div class="text-center mt-4 pt-3">
        <a href="{{ route('password.request') }}" class="text-decoration-none small">
            Забыли пароль?
        </a>
    </div>
</form>
@endsection
