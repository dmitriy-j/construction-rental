<x-auth-layout title="Подтверждение пароля">
    <div class="alert alert-warning mb-4">
        <i class="bi bi-shield-lock me-2"></i>
        Это защищенная зона приложения. Пожалуйста, подтвердите ваш пароль.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="form-label">Пароль</label>
            <div class="input-group">
                <input id="password" 
                       type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="Введите ваш пароль">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            @error('password')
            <div class="invalid-feedback d-block">
                <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
            </div>
            @enderror
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-auth">
                <i class="bi bi-check-circle me-2"></i> Подтвердить
            </button>
        </div>
    </form>

    @section('scripts')
    <script>
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
    </script>
    @endsection
</x-auth-layout>