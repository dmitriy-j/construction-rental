<template>
  <div>
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-3 col-xl-2 mb-4">
          <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-primary"></i>
                <span class="d-none d-sm-inline">Фильтры</span>
                <button class="btn btn-sm btn-link d-sm-none p-0 ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true">
                  <i class="bi bi-chevron-down"></i>
                </button>
              </h5>
              <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">Сброс</button>
            </div>
            <div class="card-body collapse show" id="filterCollapse">
              <div class="mb-3">
                <label class="form-label fw-semibold small">Поиск</label>
                <input type="text" class="form-control form-control-sm" v-model="filters.search" @input="onSearchInput">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Категория</label>
                <select class="form-select form-select-sm" v-model="filters.category" @change="loadEquipment">
                  <option value="">Все</option>
                  <option v-for="c in filterOptions.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Локация</label>
                <select class="form-select form-select-sm" v-model="filters.location" @change="loadEquipment">
                  <option value="">Все</option>
                  <option v-for="l in filterOptions.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Цена (₽/час)</label>
                <div class="row g-1">
                  <div class="col-6"><input type="number" class="form-control form-control-sm" placeholder="от" v-model.number="filters.min_price" @change="loadEquipment"></div>
                  <div class="col-6"><input type="number" class="form-control form-control-sm" placeholder="до" v-model.number="filters.max_price" @change="loadEquipment"></div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Сортировка</label>
                <select class="form-select form-select-sm" v-model="sort" @change="loadEquipment">
                  <option value="newest">Новые</option>
                  <option value="price_asc">Дешёвые</option>
                  <option value="price_desc">Дорогие</option>
                  <option value="popular">Популярные</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">На странице</label>
                <select class="form-select form-select-sm" v-model="perPage" @change="loadEquipment">
                  <option value="12">12</option>
                  <option value="24">24</option>
                  <option value="48">48</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-9 col-xl-10">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Каталог техники <small class="text-muted fs-6">({{ total }} ед.)</small></h2>
            <span v-if="loading" class="text-muted small"><i class="bi bi-hourglass-split"></i> Загрузка...</span>
          </div>
          <div v-if="!loading" class="row g-3">
            <div v-for="eq in equipment" :key="eq.id" class="col-xl-3 col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm">
                <div class="position-relative">
                  <img :src="getThumbnailUrl(eq)" class="card-img-top" alt="" loading="lazy" style="height:200px;object-fit:cover;">
                  <span v-if="eq.is_platform_owned" class="position-absolute top-0 end-0 m-2 badge bg-primary"><i class="bi bi-building-gear"></i> Платформа</span>
                </div>
                <div class="card-body d-flex flex-column">
                  <h6 class="fw-bold text-truncate">{{ eq.brand }} {{ eq.model }}</h6>
                  <p class="small text-muted mb-1">{{ eq.title }}</p>
                  <div class="d-flex justify-content-between small text-muted mb-2">
                    <span><i class="bi bi-calendar"></i> {{ eq.year }}</span>
                    <span><i class="bi bi-geo-alt"></i> {{ eq.location_name }}</span>
                  </div>
                  <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="fs-5 fw-bold text-primary">{{ eq.final_price }} ₽<small class="fw-normal text-muted fs-6">/час</small></span>
                      <span v-if="eq.rating > 0" class="text-warning small"><i class="bi bi-star-fill"></i> {{ eq.rating }}</span>
                    </div>
                    <div class="d-grid gap-1 mt-2">
                      <a :href="'/catalog/' + eq.id" class="btn btn-outline-primary btn-sm">Подробнее</a>
                      <button class="btn btn-primary btn-sm" @click="openAddModal(eq)"><i class="bi bi-cart-plus"></i> В корзину</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
          <div v-if="!loading && equipment.length === 0" class="text-center py-5">
            <i class="bi bi-box-seam display-1 text-muted"></i>
            <p class="mt-3 text-muted">Техника не найдена</p>
          </div>
          <nav v-if="meta.last_page > 1" class="mt-4 d-flex justify-content-between align-items-center">
            <span class="small text-muted">Стр. {{ meta.current_page }}/{{ meta.last_page }}</span>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: meta.current_page <= 1 }"><button class="page-link" @click="changePage(meta.current_page - 1)">&laquo;</button></li>
              <li class="page-item" v-for="p in pages" :key="p" :class="{ active: p === meta.current_page }"><button class="page-link" @click="changePage(p)">{{ p }}</button></li>
              <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }"><button class="page-link" @click="changePage(meta.current_page + 1)">&raquo;</button></li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Модальное окно добавления в корзину -->
    <div v-if="showModal" class="modal-backdrop fade show" @click="closeModal"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="z-index:10060;">
      <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:700px;">
        <div class="modal-content">
          <div class="modal-header bg-white">
            <h5 class="modal-title">
              <i class="bi bi-cart-plus text-primary me-2"></i>
              Добавить: {{ modalEquipment.brand }} {{ modalEquipment.model }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card bg-light h-100">
                  <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-calendar-range me-2"></i>Период аренды</h6>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Дата начала</label>
                      <input type="date" class="form-control" v-model="form.start_date" :min="minDate" @change="recalculatePrice">
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Дата окончания</label>
                      <input type="date" class="form-control" v-model="form.end_date" :min="form.start_date || minDate" @change="recalculatePrice">
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Адрес доставки (опционально)</label>
                      <input type="text" class="form-control" v-model="form.address" placeholder="г. Москва, ул. Строителей, д. 10">
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card bg-light h-100">
                  <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-gear me-2"></i>Условия работы</h6>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Смен в сутки</label>
                      <select class="form-select" v-model.number="form.shifts_per_day" @change="recalculatePrice">
                        <option :value="1">1 смена</option>
                        <option :value="2">2 смены</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Часов в смене</label>
                      <select class="form-select" v-model.number="form.hours_per_shift" @change="recalculatePrice">
                        <option v-for="h in [1,2,3,4,5,6,7,8,9,10,11,12]" :key="h" :value="h">{{ h }} ч</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Количество единиц</label>
                      <input type="number" class="form-control" v-model.number="form.quantity" min="1" @change="recalculatePrice">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Расчёт стоимости — без комиссии -->
            <div v-if="priceData" class="card mt-3 border-primary">
              <div class="card-body">
                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-calculator me-2"></i>Предварительный расчёт</h6>
                <div class="row text-center">
                  <div class="col-4 border-end">
                    <div class="small text-muted">Дней аренды</div>
                    <div class="fs-5 fw-bold">{{ priceData.days }}</div>
                  </div>
                  <div class="col-4 border-end">
                    <div class="small text-muted">Всего часов</div>
                    <div class="fs-5 fw-bold">{{ priceData.total_hours }}</div>
                  </div>
                  <div class="col-4">
                    <div class="small text-muted">Ставка/час</div>
                    <div class="fs-5 fw-bold text-primary">{{ priceData.final_price_per_hour }} ₽</div>
                  </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                  <div class="small text-muted">{{ priceData.days }} дн. × {{ priceData.total_hours }} ч работы</div>
                  <div class="text-end">
                    <div class="small text-muted">Итоговая стоимость</div>
                    <div class="fs-4 fw-bold text-success">{{ priceData.total_final }} ₽</div>
                  </div>
                </div>
                <div v-if="!priceData.is_available" class="alert alert-warning mt-2 mb-0 py-2 small">
                  <i class="bi bi-exclamation-triangle me-1"></i> Техника недоступна на выбранные даты
                </div>
              </div>
            </div>
            <div v-if="error" class="alert alert-danger mt-3 py-2 small">{{ error }}</div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" @click="closeModal">Отмена</button>
            <button type="button" class="btn btn-primary" @click="addToCart" :disabled="adding || !priceData?.is_available">
              <span v-if="adding" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-cart-plus me-1"></i>
              {{ adding ? 'Добавление...' : 'Добавить в корзину' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import '../eventBus.js';

export default {
  data() {
    return {
      equipment: [],
      meta: { current_page: 1, last_page: 1, total: 0 },
      filters: { category: '', location: '', min_price: null, max_price: null, search: '' },
      sort: 'newest', perPage: 12, loading: false,
      filterOptions: { categories: [], locations: [] }, searchTimer: null,
      showModal: false,
      modalEquipment: null,
      form: {
        start_date: '',
        end_date: '',
        shifts_per_day: 1,
        hours_per_shift: 8,
        quantity: 1,
        address: '',
      },
      priceData: null,
      adding: false,
      error: null,
    };
  },
  computed: {
    total() { return this.meta.total; },
    minDate() {
      const d = new Date();
      d.setDate(d.getDate() + 1);
      return d.toISOString().split('T')[0];
    },
    pages() {
      let p = [], start = Math.max(1, this.meta.current_page - 2);
      let end = Math.min(this.meta.last_page, start + 4);
      for (let i = start; i <= end; i++) p.push(i);
      return p;
    }
  },
  methods: {
    getThumbnailUrl(eq) {
      if (eq.images && eq.images.length > 0) {
        const mainImg = eq.images.find(i => i.is_main) || eq.images[0];
        return mainImg.thumbnail_url || mainImg.url || '/images/no-image.svg';
      }
      return eq.main_image_url || '/images/no-image.svg';
    },
    async loadEquipment() {
      this.loading = true;
      const params = new URLSearchParams({
        page: this.meta.current_page, per_page: this.perPage, sort: this.sort,
        category: this.filters.category, location: this.filters.location,
        min_price: this.filters.min_price || '', max_price: this.filters.max_price || '',
        search: this.filters.search,
      });
      try {
        const res = await fetch('/api/equipment?' + params.toString());
        const data = await res.json();
        this.equipment = data.data || [];
        this.meta = data.meta || { current_page: 1, last_page: 1, total: 0 };
        if (data.filters) this.filterOptions = data.filters;
      } catch (e) { console.error(e); }
      this.loading = false;
    },
    changePage(page) {
      if (page < 1 || page > this.meta.last_page) return;
      this.meta.current_page = page;
      this.loadEquipment();
    },
    onSearchInput() {
      clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(() => this.loadEquipment(), 300);
    },
    resetFilters() {
      this.filters = { category: '', location: '', min_price: null, max_price: null, search: '' };
      this.sort = 'newest'; this.meta.current_page = 1;
      this.loadEquipment();
    },
    openAddModal(eq) {
      this.modalEquipment = eq;
      const d = new Date();
      d.setDate(d.getDate() + 1);
      const tomorrow = d.toISOString().split('T')[0];
      const dayAfter = new Date(d);
      dayAfter.setDate(dayAfter.getDate() + 1);
      this.form = {
        start_date: tomorrow,
        end_date: dayAfter.toISOString().split('T')[0],
        shifts_per_day: 1,
        hours_per_shift: 8,
        quantity: 1,
        address: '',
      };
      this.priceData = null;
      this.error = null;
      this.showModal = true;
      document.body.classList.add('modal-open');
      this.recalculatePrice();
    },
    closeModal() {
      this.showModal = false;
      document.body.classList.remove('modal-open');
    },
    async recalculatePrice() {
      if (!this.form.start_date || !this.form.end_date || !this.modalEquipment) return;
      try {
        const params = new URLSearchParams({
          start_date: this.form.start_date,
          end_date: this.form.end_date,
          shifts_per_day: this.form.shifts_per_day || 1,
          hours_per_shift: this.form.hours_per_shift || 8,
          quantity: this.form.quantity || 1,
        });
        const res = await fetch(`/api/equipment/${this.modalEquipment.id}/price?` + params.toString());
        const data = await res.json();
        if (data.success) {
          this.priceData = data;
        } else {
          console.error('Price API error:', data);
        }
      } catch (e) {
        console.error('Price recalculation error:', e);
      }
    },
    async addToCart() {
      if (!this.form.start_date || !this.form.end_date) {
        this.error = 'Выберите даты аренды';
        return;
      }
      if (!this.priceData || !this.priceData.is_available) {
        this.error = 'Техника недоступна на выбранные даты';
        return;
      }
      this.adding = true;
      this.error = null;
      try {
        const res = await fetch('/api/cart', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
          body: JSON.stringify({
            equipment_id: this.modalEquipment.id,
            start_date: this.form.start_date,
            end_date: this.form.end_date,
            shifts_per_day: this.form.shifts_per_day,
            hours_per_shift: this.form.hours_per_shift,
            quantity: this.form.quantity,
            address: this.form.address,
          }),
        });
        const data = await res.json();
        if (data.success) {
          this.closeModal();
          if (window.cartBus) {
            window.cartBus.emit('cart-updated', data);
          }
        } else {
          this.error = data.error || 'Ошибка добавления в корзину';
        }
      } catch (e) {
        console.error('Add to cart error:', e);
        this.error = 'Ошибка соединения с сервером';
      }
      this.adding = false;
    }
  },
  mounted() { this.loadEquipment(); }
};
</script>
