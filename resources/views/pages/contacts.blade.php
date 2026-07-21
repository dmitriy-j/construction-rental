@extends('layouts.app')

@section('title', 'Контакты — Федеральная Арендная Платформа')

@section('content')
{{-- ============================================================
    1. HERO-БЛОК
    ============================================================ --}}
<section class="page-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-white mb-3 animate__animated animate__fadeInUp">
                    Контакты
                </h1>
                <p class="lead text-white-50 mb-0 animate__animated animate__fadeInUp animate__delay-1s">
                    Свяжитесь с нами любым удобным способом
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    2. КОНТАКТНАЯ ИНФОРМАЦИЯ
    ============================================================ --}}
<section class="page-section">
    <div class="container">
        <div class="row g-4">
            {{-- Левая колонка: контакты --}}
            <div class="col-lg-5">
                <h2 class="page-section-title fw-bold mb-4 d-inline-block">Как с нами связаться</h2>

                @if($platform && $platform->exists)
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-circle bg-primary flex-shrink-0 me-3">
                        <i class="bi bi-telephone-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Телефон</h5>
                        <a href="tel:{{ $platform->phone }}" class="text-decoration-none fs-5 text-primary">{{ $platform->phone }}</a>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="icon-circle bg-success flex-shrink-0 me-3">
                        <i class="bi bi-envelope-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Email</h5>
                        <a href="mailto:{{ $platform->email }}" class="text-decoration-none fs-5">{{ $platform->email }}</a>
                    </div>
                </div>

                @if($platform->physical_address)
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-circle bg-info flex-shrink-0 me-3">
                        <i class="bi bi-geo-alt-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Адрес</h5>
                        <p class="mb-0 text-muted">{{ $platform->physical_address }}</p>
                    </div>
                </div>
                @endif

                @if($platform->legal_address)
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-circle bg-secondary flex-shrink-0 me-3">
                        <i class="bi bi-building fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Юридический адрес</h5>
                        <p class="mb-0 text-muted">{{ $platform->legal_address }}</p>
                    </div>
                </div>
                @endif

                @if($platform->ceo_name)
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-circle bg-warning flex-shrink-0 me-3">
                        <i class="bi bi-person-badge fs-4 text-dark"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Руководство</h5>
                        <p class="mb-0 text-muted">{{ $platform->ceo_name }}{{ $platform->ceo_position ? ' — ' . $platform->ceo_position : '' }}</p>
                    </div>
                </div>
                @endif

                @if($platform->additional_phones && count($platform->additional_phones) > 0)
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-circle bg-danger flex-shrink-0 me-3">
                        <i class="bi bi-telephone-plus fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Доп. телефоны</h5>
                        @foreach($platform->additional_phones as $phone)
                            <p class="mb-0 text-muted">{{ $phone }}</p>
                        @endforeach
                    </div>
                </div>
                @endif

                @endif

                {{-- Социальные сети --}}
                @if($platform && $platform->website)
                <div class="d-flex align-items-start mb-4">
                    <div class="icon-circle bg-primary flex-shrink-0 me-3">
                        <i class="bi bi-globe fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Сайт</h5>
                        <a href="{{ $platform->website }}" target="_blank" class="text-decoration-none">{{ $platform->website }}</a>
                    </div>
                </div>
                @endif

                {{-- Реквизиты --}}
                @if($platform && $platform->exists)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-file-text text-primary me-2"></i>Реквизиты
                        </h5>
                        @if($platform->inn)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ИНН</span>
                            <span class="fw-semibold">{{ $platform->inn }}</span>
                        </div>
                        @endif
                        @if($platform->kpp)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">КПП</span>
                            <span class="fw-semibold">{{ $platform->kpp }}</span>
                        </div>
                        @endif
                        @if($platform->ogrn)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ОГРН</span>
                            <span class="fw-semibold">{{ $platform->ogrn }}</span>
                        </div>
                        @endif
                        @if($platform->okpo)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">ОКПО</span>
                            <span class="fw-semibold">{{ $platform->okpo }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Правая колонка: карта и форма --}}
            <div class="col-lg-7">
                {{-- Карта --}}
                @if($platform && $platform->exists && $platform->physical_address)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-map me-2"></i>Мы на карте</h5>
                    </div>
                    <div class="card-body p-0 position-relative" style="min-height: 400px;">
                        <div id="contacts-map" style="width: 100%; height: 400px; background: #e9ecef;"></div>
                        <div id="contacts-map-loader"
                             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                                    background: rgba(255,255,255,0.95); display: flex;
                                    align-items: center; justify-content: center; z-index: 1000;">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                                <p class="text-muted">Загрузка карты...</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Форма обратной связи (упрощённая) --}}
                <div class="contact-form-card bg-white rounded-4 shadow-sm p-4 p-lg-5">
                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-chat-dots text-primary me-2"></i>Напишите нам
                    </h4>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form id="contactForm" action="{{ route('home.contact') }}" method="POST" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ваше имя *</label>
                                <input type="text" class="form-control form-control-lg" name="name" required placeholder="Иван Петров">
                                <div class="invalid-feedback">Пожалуйста, укажите ваше имя</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Телефон *</label>
                                <input type="tel" class="form-control form-control-lg" name="phone" required placeholder="+7 (999) 123-45-67">
                                <div class="invalid-feedback">Пожалуйста, укажите номер телефона</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Email <span class="text-muted">(необязательно)</span></label>
                                <input type="email" class="form-control form-control-lg" name="email" placeholder="ivan@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Сообщение</label>
                                <textarea class="form-control form-control-lg" name="message" rows="4" placeholder="Опишите ваш вопрос..."></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" id="contactSubmitBtn">
                                <i class="bi bi-send me-2"></i> Отправить
                            </button>
                        </div>
                        <div id="contactSuccess" class="alert alert-success mt-3 d-none">
                            <i class="bi bi-check-circle me-2"></i> <span id="contactSuccessMsg"></span>
                        </div>
                        <div id="contactError" class="alert alert-danger mt-3 d-none">
                            <i class="bi bi-exclamation-circle me-2"></i> <span id="contactErrorMsg"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
{{-- Яндекс.Карты --}}
@if($platform && $platform->exists && $platform->physical_address)
<script src="https://api-maps.yandex.ru/2.1/?apikey=bd8c3925-2d3e-448a-b6d3-c2c53a112615&lang=ru_RU" type="text/javascript"></script>
<script>
(function() {
    'use strict';

    const mapContainer = document.getElementById('contacts-map');
    const mapLoader = document.getElementById('contacts-map-loader');
    if (!mapContainer) return;

    function showStaticMap() {
        const width = Math.max(mapContainer.offsetWidth, 600);
        const src = `https://static-maps.yandex.ru/1.x/?ll=37.652714,55.863631&z=16&size=${width},400&l=map&pt=37.652714,55.863631,pm2dbl`;
        mapContainer.innerHTML = `<img src="${src}" alt="Карта" style="width:100%;height:100%;object-fit:cover;">`;
        if (mapLoader) mapLoader.style.display = 'none';
    }

    function initMap() {
        if (typeof ymaps === 'undefined') { setTimeout(initMap, 200); return; }
        ymaps.ready(function() {
            try {
                var map = new ymaps.Map('contacts-map', {
                    center: [55.863631, 37.652714],
                    zoom: 16,
                    controls: ['zoomControl', 'fullscreenControl']
                }, { suppressMapOpenBlock: true });
                var pm = new ymaps.Placemark([55.863631, 37.652714], {
                    hintContent: 'Федеральная Арендная Платформа',
                    balloonContentBody: '<strong>Адрес:</strong> {{ $platform->physical_address }}<br><strong>Телефон:</strong> {{ $platform->phone ?? '+7 (929) 533-32-06' }}'
                }, { preset: 'islands#blueBusinessIcon' });
                map.geoObjects.add(pm);
                setTimeout(function() {
                    if (mapLoader) mapLoader.style.display = 'none';
                }, 500);
                map.events.add('error', showStaticMap);
            } catch(e) { showStaticMap(); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }

    setTimeout(function() {
        if (mapLoader && mapLoader.style.display !== 'none') showStaticMap();
    }, 6000);
})();
</script>
@endif

{{-- Валидация и отправка формы --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    var contactForm = document.getElementById('contactForm');
    if (!contactForm) return;

    var submitBtn = document.getElementById('contactSubmitBtn');
    var successAlert = document.getElementById('contactSuccess');
    var successMsg = document.getElementById('contactSuccessMsg');
    var errorAlert = document.getElementById('contactError');
    var errorMsg = document.getElementById('contactErrorMsg');

    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        successAlert.classList.add('d-none');
        errorAlert.classList.add('d-none');

        if (!contactForm.checkValidity()) {
            e.stopPropagation();
            contactForm.classList.add('was-validated');
            return;
        }

        var formData = new FormData(contactForm);
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Отправка...';

        fetch(contactForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                successMsg.textContent = data.message;
                successAlert.classList.remove('d-none');
                contactForm.reset();
                contactForm.classList.remove('was-validated');
            } else {
                errorMsg.textContent = data.message || 'Произошла ошибка.';
                errorAlert.classList.remove('d-none');
            }
        })
        .catch(function() {
            errorMsg.textContent = 'Произошла ошибка. Попробуйте позже.';
            errorAlert.classList.remove('d-none');
        })
        .finally(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send me-2"></i> Отправить';
        });
    });

    // Появление карточек при скролле
    var cards = document.querySelectorAll('.feature-card, .team-card');
    if (cards.length) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        cards.forEach(function(el) { el.style.opacity = '0'; observer.observe(el); });
    }
});
</script>
@endpush
