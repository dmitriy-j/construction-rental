{{-- resources/views/profile/partials/personal-information.blade.php --}}
<form method="post" action="{{ route('profile.update') }}" class="row g-3">
    @csrf
    @method('patch')

    <div class="col-md-6">
        <label for="name" class="form-label">ФИО *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror"
               id="name" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email *</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">Телефон</label>
        <input type="text" class="form-control @error('phone') is-invalid @enderror"
               id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="position" class="form-label">Должность</label>
        <input type="text" class="form-control @error('position') is-invalid @enderror"
               id="position" name="position" value="{{ old('position', $user->position) }}">
        @error('position')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Сохранить изменения
        </button>

        @if (session('status') === 'profile-updated')
            <span class="text-success ms-3">
                <i class="bi bi-check-lg me-1"></i>Сохранено
            </span>
        @endif
    </div>
</form>

@if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
<div class="alert alert-warning mt-3" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Ваш email не подтвержден.
    <form method="post" action="{{ route('verification.send') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-link p-0 align-baseline">
            Отправить ссылку для подтверждения
        </button>
    </form>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-2 text-success">
            Новая ссылка для подтверждения отправлена на ваш email.
        </div>
    @endif
</div>
@endif
