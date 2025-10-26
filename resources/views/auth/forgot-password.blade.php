<x-auth-layout title="Восстановление пароля">
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        Забыли пароль? Укажите ваш email и мы вышлем ссылку для сброса.
    </div>

    <!-- Session Status -->
    @if (session('status'))
    <div class="alert alert-success mb-4">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   name="email" value="{{ old('email') }}" 
                   required autofocus
                   placeholder="Ваш email">
            @error('email')
            <div class="invalid-feedback">
                <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
            </div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('login') }}" class="text-primary">
                <i class="bi bi-arrow-left me-1"></i> Назад к входу
            </a>
            
            <button type="submit" class="btn btn-primary btn-auth">
                <i class="bi bi-envelope me-2"></i> Отправить ссылку
            </button>
        </div>
    </form>
</x-auth-layout>