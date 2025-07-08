@extends('layouts.app')

@section('content')



    <!-- 2. Новости -->
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <h3 class="mb-4">Новости</h3>
            <div class="news-item mb-3">
                <h5>Новая партия экскаваторов</h5>
                <small class="text-muted">12.04.2023</small>
            </div>
            <div class="news-item">
                <h5>Скидки на аренду в мае</h5>
                <small class="text-muted">10.04.2023</small>
            </div>
            <h3 class="mb-4">Срочные заявки</h3>
    
    <!-- Таблица -->
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th>Техника</th>
            <th>Климат</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><i class="bi bi-truck me-2"></i>Экскаватор JCB</td>
            <td>Стандартный</td>
            <td>
              <button class="btn btn-sm btn-outline-primary">
                <i class="bi bi-lightning-charge"></i> Срочно
              </button>
            </td>
          </tr>
          <tr>
            <td><i class="bi bi-truck me-2"></i>Бульдозер CAT</td>
            <td>Морозостойкий</td>
            <td>
              <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-clock"></i> До 18:00
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="row mt-5">
  <!-- Карточка "Арендатор" -->
  <div class="col-md-6 mb-4">
    <div class="card h-100 border-primary">
      <div class="card-body text-center">
        <i class="bi bi-truck text-primary" style="font-size: 3rem;"></i>
        <h3 class="my-3">Арендатор</h3>
        <p class="text-muted">
          Возьмите строительную технику в аренду для ваших проектов
        </p>
        <a href="#" class="btn btn-primary btn-lg">
          <i class="bi bi-search me-2"></i> Найти технику
        </a>
      </div>
    </div>
  </div>

  <!-- Карточка "Арендодатель" -->
  <div class="col-md-6 mb-4">
    <div class="card h-100 border-warning">
      <div class="card-body text-center">
        <i class="bi bi-cash-coin text-warning" style="font-size: 3rem;"></i>
        <h3 class="my-3">Арендодатель</h3>
        <p class="text-muted">
          Сдайте свою технику в аренду и получайте доход
        </p>
        <a href="#" class="btn btn-warning btn-lg">
          <i class="bi bi-plus-circle me-2"></i> Добавить технику
        </a>
      </div>
    </div>
  </div>
</div>

    <div class="mt-3 text-muted">
      <small><i class="bi bi-info-circle"></i> Для срочных заявок — обработка в течение 30 минут.</small>
    </div>
        </div>
    </div>
</div>
@endsection

