<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="{{ url('/') }}" style="
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    font-size: 1.8rem;
    color: white;
    background: rgba(0, 0, 0, 0.7);
    padding: 0.2rem 1rem;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
">
    Rent<span style="color: #00d2ff;">Tech</span>
</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
  <li class="nav-item">
    <a class="nav-link" href="/about">
      <i class="bi bi-buildings me-2"></i>О компании
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="/requests">
      <i class="bi bi-clipboard-check me-2"></i>Заявки
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="/catalog">
      <i class="bi bi-list-ul me-2"></i>Каталог
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="/free">
      <i class="bi bi-check-circle me-2"></i>Свободная техника
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="/repair">
      <i class="bi bi-tools me-2"></i>Ремонт техники
    </a>
  </li>
</ul>

<ul class="navbar-nav ms-auto">
  <li class="nav-item">
    <a class="nav-link" href="/cooperation">
  <i class="bi bi-people me-2"></i>Сотрудничество
</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="/contacts">
      <i class="bi bi-telephone me-2"></i>Контакты
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="/jobs">
      <i class="bi bi-briefcase me-2"></i>Вакансии
    </a>
  </li>
</ul>
    </div>
  </div>
</nav>