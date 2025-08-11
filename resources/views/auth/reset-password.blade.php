@extends('layouts.auth')

@section('title', 'Сброс пароля')
@section('page-title', 'Сброс пароля')
@section('background-text', 'Установите новый пароль для вашей учетной записи.')

@section('content')
<form method="POST" action="{{ route('password.update') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="auth-form-group mb-4">
        <p class="text-center">
            <i class="bi bi-shield-lock fs-2 text-primary mb-3 d-block"></i>
            Установите новый пароль для вашей учетной записи.
        </p>
    </div>

    <!-- Email -->
    <div class="auth-form-group">
        <label for="email" class="form-label small mb-1">Рабочий Email</label>
        <div class="auth-input-group">
            <i class="bi bi-envelope auth-input-icon"></i>
            <input id="email" type="email"
                class="auth-input @error('email') is-invalid @enderror"
                name="email" value="{{ old('email', $request->email) }}"
                required autofocus placeholder="ваш@email.com">
        </div>
        @error('email')
            <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="auth-form-group">
        <label for="password" class="form-label small mb-1">Новый пароль</label>
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

    <!-- Confirm Password -->
    <div class="auth-form-group">
        <label for="password_confirmation" class="form-label small mb-1">Подтвердите пароль</label>
        <div class="auth-input-group">
            <i class="bi bi-lock auth-input-icon"></i>
            <input id="password_confirmation" type="password"
                class="auth-input"
                name="password_confirmation" required placeholder="••••••••">
        </div>
    </div>

    <!-- Submit Button -->
    <div class="auth-form-group">
        <button type="submit" class="auth-btn">
            <i class="bi bi-key"></i> Сбросить пароль
        </button>
    </div>
</form>
@endsection
