@extends('layouts.app')

@section('content')
<div class="verify-email-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <!-- Карточка с анимацией -->
                <div class="auth-card card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white py-3">
                        <h3 class="mb-0 text-center">
                            <i class="bi bi-envelope-check me-2"></i>
                            Подтверждение Email
                        </h3>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Иконка статуса -->
                        <div class="text-center mb-4">
                            <div class="email-icon-animation">
                                <i class="bi bi-envelope fs-1 text-primary"></i>
                                <i class="bi bi-arrow-right fs-4 text-muted mx-2"></i>
                                <i class="bi bi-check-circle fs-1 text-success"></i>
                            </div>
                        </div>

                        @if (session('resent'))
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                {{ __('Новая ссылка для подтверждения была отправлена на ваш email.') }}
                            </div>
                        @endif

                        <div class="text-center mb-4">
                            <p class="lead">
                                {{ __('Пожалуйста, проверьте ваш email') }} <strong>{{ Auth::user()->email }}</strong>
                                {{ __('для завершения регистрации.') }}
                            </p>
                            <p class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Если письмо не пришло, проверьте папку "Спам"
                            </p>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <form method="POST" action="{{ route('verification.resend') }}">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-primary btn-gradient"
                                        id="resend-button">
                                    <i class="bi bi-send-fill me-2"></i>
                                    <span id="resend-text">Отправить письмо снова</span>
                                    <span id="timer-text" class="d-none"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Дополнительная информация -->
                <div class="text-center mt-4">
                    <p class="text-muted small">
                        Письмо не приходит? <a href="{{ route('contact') }}" class="text-primary">Свяжитесь с поддержкой</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@section('styles')
<style>
    .verify-email-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
    }
    
    .auth-card {
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    
    .auth-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .email-icon-animation {
        display: flex;
        justify-content: center;
        align-items: center;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .btn-gradient {
        background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
        border: none;
        padding: 10px 25px;
        transition: all 0.3s;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(58, 123, 213, 0.3);
    }
</style>
@endsection

@section('scripts')
<script>
    // Таймер для повторной отправки
    let timer = 60;
    const resendBtn = document.getElementById('resend-button');
    const resendText = document.getElementById('resend-text');
    const timerText = document.getElementById('timer-text');
    
    function updateTimer() {
        if (timer > 0) {
            resendBtn.disabled = true;
            resendText.classList.add('d-none');
            timerText.classList.remove('d-none');
            timerText.innerHTML = `Повторная отправка через ${timer} сек`;
            timer--;
            setTimeout(updateTimer, 1000);
        } else {
            resendBtn.disabled = false;
            timerText.classList.add('d-none');
            resendText.classList.remove('d-none');
        }
    }
    
    // Запускаем таймер при загрузке
    document.addEventListener('DOMContentLoaded', updateTimer);
</script>
@endsection
@endsection