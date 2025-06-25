<x-auth-layout title="Сброс пароля">
    <div class="alert alert-info mb-4">
        <i class="bi bi-shield-lock me-2"></i>
        Укажите новый пароль для вашего аккаунта
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" 
                   type="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   name="email" 
                   value="{{ old('email', $request->email) }}" 
                   required 
                   autofocus
                   placeholder="Ваш email">
            @error('email')
            <div class="invalid-feedback">
                <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
            </div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Новый пароль</label>
            <div class="input-group">
                <input id="password" 
                       type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password"
                       required
                       autocomplete="new-password"
                       placeholder="Придумайте новый пароль">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <small class="text-muted">Минимум 8 символов</small>
            @error('password')
            <div class="invalid-feedback d-block">
                <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
            </div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Подтвердите пароль</label>
            <input id="password_confirmation" 
                   type="password"
                   class="form-control"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   placeholder="Повторите новый пароль">
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-auth">
                <i class="bi bi-arrow-repeat me-2"></i> Сбросить пароль
            </button>
        </div>
    </form>

    @section('scripts')
    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const strengthBadge = document.getElementById('password-strength');
            if (!strengthBadge) return;
            
            const strength = {
                0: "Слишком слабый",
                1: "Слабый",
                2: "Средний",
                3: "Сильный",
                4: "Очень сильный"
            };
            
            const val = this.value;
            const result = zxcvbn(val);
            
            strengthBadge.textContent = `${strength[result.score]} ${val.length > 0 ? '(взлом за ' + result.crack_times_display.offline_slow_hashing_1e4_per_second + ')' : ''}`;
            strengthBadge.className = 'badge mt-2 ' + 
                (result.score < 2 ? 'bg-danger' : 
                 result.score < 4 ? 'bg-warning' : 'bg-success');
        });
    </script>
    @endsection
</x-auth-layout>