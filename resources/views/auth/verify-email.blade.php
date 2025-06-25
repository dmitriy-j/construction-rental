<x-auth-layout title="Подтверждение email">
    <div class="alert alert-info mb-4">
        <i class="bi bi-envelope-check me-2"></i>
        Спасибо за регистрацию! Прежде чем начать, подтвердите ваш email, перейдя по ссылке в письме.
        Если вы не получили письмо, мы с радостью отправим его снова.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle me-2"></i>
            Новое письмо с подтверждением было отправлено на указанный email.
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mt-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-auth">
                <i class="bi bi-envelope-arrow-up me-2"></i> Отправить письмо повторно
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-box-arrow-right me-2"></i> Выйти
            </button>
        </form>
    </div>

    <div class="text-center mt-5">
        <img src="https://cdn-icons-png.flaticon.com/512/3178/3178283.png" 
             alt="Письмо" 
             style="max-width: 200px; opacity: 0.8;">
    </div>
</x-auth-layout>