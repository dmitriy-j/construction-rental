@extends('layouts.auth')

@section('title', 'Вход в систему')

@section('content')
<form method="POST" action="{{ route('login') }}" class="px-3">
    @csrf

    <!-- Session Status -->
    @if(session('status'))
    <div class="alert alert-success mb-3">
        {{ session('status') }}
    </div>
    @endif

    <!-- Email -->
    <div class="mb-4">
        <label for="email" class="form-label small text-muted mb-1">Рабочий Email</label>
        <div class="input-group">
            <span class="input-group-text bg-transparent p-2">
                <i class="bi bi-envelope text-primary"></i>
            </span>
            <input id="email" type="email"
                class="form-control @error('email') is-invalid @enderror"
                name="email" value="{{ old('email') }}"
                required autofocus placeholder="ваш@email.com">
        </div>
        @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="mb-4">
        <label for="password" class="form-label small text-muted mb-1">Пароль</label>
        <div class="input-group">
            <span class="input-group-text bg-transparent p-2">
                <i class="bi bi-lock text-primary"></i>
            </span>
            <input id="password" type="password"
                class="form-control @error('password') is-invalid @enderror"
                name="password" required placeholder="••••••••">
        </div>
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <!-- Remember Me -->
    <div class="mb-4 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label small" for="remember">
            Запомнить на этом устройстве
        </label>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('password.request') }}" class="text-decoration-none small text-primary">
            Забыли пароль?
        </a>
        <button type="submit" class="btn btn-primary px-4 py-2">
            <i class="bi bi-box-arrow-in-right me-2"></i>Войти
        </button>
    </div>
</form>
@endsection
