@extends('layouts.auth')

@section('title', 'Подтверждение email')
@section('page-title', 'Подтвердите ваш email')
@section('background-text', 'Проверьте вашу электронную почту и перейдите по ссылке для подтверждения.')

@section('content')
<div class="text-center">
    <div class="auth-form-group">
        <i class="bi bi-envelope-check fs-1 text-primary mb-3"></i>
        <h3 class="mb-3">Подтвердите ваш email</h3>

        <p class="mb-4">
            На ваш email <span class="fw-bold">{{ auth()->user()->email }}</span>
            была отправлена ссылка для подтверждения. Пожалуйста, проверьте вашу почту и перейдите по ссылке.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-4">
                <i class="bi bi-check-circle me-2"></i>
                Новая ссылка подтверждения была отправлена на ваш email!
            </div>
        @endif

        <div class="d-flex flex-column gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="auth-btn w-100">
                    <i class="bi bi-envelope-arrow-up"></i> Отправить ссылку повторно
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-box-arrow-left me-1"></i> Выйти
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
