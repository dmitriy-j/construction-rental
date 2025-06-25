<x-auth-layout title="Подтверждение email">
    <div class="email-verification-page">
        <div class="alert alert-info mb-4 d-flex align-items-center">
            <i class="bi bi-envelope-check fs-3 me-3"></i>
            <div>
                <h5 class="alert-heading">Подтвердите ваш email</h5>
                <p class="mb-0">Мы отправили письмо с ссылкой для подтверждения на {{ Auth::user()->email }}. 
                Пожалуйста, проверьте вашу почту.</p>
            </div>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-4 d-flex align-items-center">
                <i class="bi bi-check2-circle fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading">Письмо отправлено!</h5>
                    <p class="mb-0">Новая ссылка для подтверждения была отправлена на ваш email.</p>
                </div>
            </div>
        @endif

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-5 gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-100">
                @csrf
                <button type="submit" 
                        class="btn btn-primary btn-auth w-100 py-2"
                        id="resend-button">
                    <i class="bi bi-envelope-arrow-up me-2"></i> 
                    <span id="resend-text">Отправить письмо повторно</span>
                    <span id="timer-text" class="d-none"></span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-100">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100 py-2">
                    <i class="bi bi-box-arrow-right me-2"></i> Выйти
                </button>
            </form>
        </div>

        <div class="text-center mt-5">
            <img src="https://cdn-icons-png.flaticon.com/512/3178/3178283.png" 
                 alt="Письмо" 
                 class="email-icon"
                 style="max-width: 180px;">
            <p class="text-muted mt-3">Не получили письмо? Проверьте папку "Спам"</p>
        </div>
    </div>

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
        
        // Сбрасываем при отправке формы
        document.querySelector('form').addEventListener('submit', function() {
            timer = 60;
            updateTimer();
        });

        // Анимация при наведении на иконку письма
        const emailIcon = document.querySelector('.email-icon');
        emailIcon.addEventListener('mouseenter', () => {
            emailIcon.style.transform = 'translateY(-5px) rotate(-5deg)';
        });
        emailIcon.addEventListener('mouseleave', () => {
            emailIcon.style.transform = '';
        });
    </script>
    @endsection
</x-auth-layout>