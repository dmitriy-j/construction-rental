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
        
        <!-- Кнопка входа/меню пользователя -->
        @auth
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              @if(Auth::user()->isPlatformAdmin())
                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                  <i class="bi bi-speedometer2 me-2"></i> Админ-панель
                </a></li>
              @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                <li><a class="dropdown-item" href="{{ route('lessor.dashboard') }}">
                  <i class="bi bi-building-gear me-2"></i> Кабинет арендодателя
                </a></li>
              @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                <li><a class="dropdown-item" href="{{ route('lessee.dashboard') }}">
                  <i class="bi bi-truck me-2"></i> Кабинет арендатора
                </a></li>
              @endif
              
              <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="bi bi-person me-2"></i> Профиль
              </a></li>
              
              <li><hr class="dropdown-divider"></li>
              
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item">
                    <i class="bi bi-box-arrow-right me-2"></i> Выйти
                  </button>
                </form>
              </li>
            </ul>
          </li>
        @else
          <li class="nav-item">
            <a class="nav-link" href="{{ route('register') }}">
              <i class="bi bi-person-plus me-1"></i> Регистрация
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-light ms-2" href="{{ route('login') }}">
              <i class="bi bi-box-arrow-in-right me-1"></i> Войти
            </a>
          </li>
        @endauth
      </ul>
    </div>
  </div>
</nav>

<style>
  .navbar-nav .nav-link {
    transition: color 0.3s ease;
    position: relative;
    padding: 0.5rem 1rem;
  }
  
  .navbar-nav .nav-link:hover {
    color: #00d2ff !important;
  }
  
  .navbar-nav .nav-link:hover::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 15%;
    width: 70%;
    height: 2px;
    background: #00d2ff;
  }
  
  .dropdown-menu {
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.05);
  }
  
  .dropdown-item {
    transition: all 0.2s;
  }
  
  .dropdown-item:hover {
    background-color: #00d2ff;
    color: white !important;
  }
  
  .btn-outline-light:hover {
    background-color: #00d2ff;
    border-color: #00d2ff;
  }
</style>