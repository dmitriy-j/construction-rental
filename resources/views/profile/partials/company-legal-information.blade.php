{{-- resources/views/profile/partials/company-legal-information.blade.php --}}
@if($user->company)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label text-muted">Юридическое наименование</label>
        <div class="form-control bg-light">{{ $user->company->legal_name }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted">ИНН</label>
        <div class="form-control bg-light">{{ $user->company->inn }}</div>
    </div>

    <div class="col-md-3">
        <label class="form-label text-muted">КПП</label>
        <div class="form-control bg-light">{{ $user->company->kpp }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-muted">ОГРН</label>
        <div class="form-control bg-light">{{ $user->company->ogrn }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-muted">ОКПО</label>
        <div class="form-control bg-light">{{ $user->company->okpo ?? 'Не указано' }}</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-muted">Система налогообложения</label>
        <div class="form-control bg-light">{{ $user->company->getTaxSystemCode() }}</div>
    </div>

    <div class="col-12">
        <label class="form-label text-muted">Юридический адрес</label>
        <div class="form-control bg-light">{{ $user->company->legal_address }}</div>
    </div>

    <div class="col-12">
        <label class="form-label text-muted">Фактический адрес</label>
        <div class="form-control bg-light">{{ $user->company->actual_address ?? $user->company->legal_address }}</div>
    </div>

    <div class="col-md-6">
        <label class="form-label text-muted">Директор</label>
        <div class="form-control bg-light">{{ $user->company->director_name }}</div>
    </div>

    <div class="col-md-6">
        <label class="form-label text-muted">Статус</label>
        <div class="form-control bg-light">
            @if($user->company->status === 'verified')
                <span class="badge bg-success">
                    <i class="bi bi-check-circle me-1"></i>Верифицирована
                </span>
            @else
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-clock me-1"></i>На проверке
                </span>
            @endif
        </div>
    </div>
</div>
@else
<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Компания не привязана к вашему аккаунту.
</div>
@endif
