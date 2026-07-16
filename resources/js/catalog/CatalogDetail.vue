<template>
  <div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a :href="'/catalog'">Каталог</a></li>
        <li class="breadcrumb-item active">{{ equipment.brand }} {{ equipment.model }}</li>
      </ol>
    </nav>

    <div class="row g-4">
      <!-- Галерея -->
      <div class="col-lg-6">
        <div class="card shadow-sm">
          <div class="card-body p-2">
            <div id="detailCarousel" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <div v-for="(img, key) in equipment.images" :key="key" class="carousel-item" :class="{ active: key === 0 }">
                  <img :src="img" class="d-block w-100 rounded" :alt="equipment.title" style="height:400px;object-fit:cover;">
                </div>
                <div v-if="!equipment.images || equipment.images.length === 0" class="carousel-item active">
                  <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height:400px;">
                    <i class="bi bi-image display-1 text-muted"></i>
                  </div>
                </div>
              </div>
              <button v-if="equipment.images && equipment.images.length > 1" class="carousel-control-prev" type="button" data-bs-target="#detailCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon bg-dark rounded-circle"></span>
              </button>
              <button v-if="equipment.images && equipment.images.length > 1" class="carousel-control-next" type="button" data-bs-target="#detailCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon bg-dark rounded-circle"></span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Информация и форма -->
      <div class="col-lg-6">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h2 class="mb-1">{{ equipment.brand }} {{ equipment.model }}</h2>
                <p class="text-muted mb-0">{{ equipment.title }}</p>
              </div>
              <span v-if="equipment.is_platform_owned" class="badge bg-primary fs-6"><i class="bi bi-building-gear"></i> Техника платформы</span>
            </div>

            <div v-if="equipment.rating > 0" class="mb-2">
              <i v-for="i in 5" :key="i" class="bi" :class="i <= Math.round(equipment.rating) ? 'bi-star-fill' : (i - 0.5 <= equipment.rating ? 'bi-star-half' : 'bi-star')" style="color:#ffc107;"></i>
              <span class="small text-muted ms-1">{{ equipment.rating }}</span>
            </div>

            <!-- Цена (только финальная, без комиссии) -->
            <div class="mb-3">
              <div class="d-flex align-items-baseline gap-2">
                <span class="display-6 fw-bold text-primary">{{ priceData ? priceData.final_price_per_hour : equipment.final_price }} ₽</span>
                <span class="text-muted">/ час</span>
              </div>
            </div>

            <hr>

            <!-- Характеристики -->
            <div class="row g-2 mb-3">
              <div class="col-6">
                <div class="bg-light rounded p-2 text-center">
                  <small class="text-muted d-block">Год выпуска</small>
                  <strong>{{ equipment.year }}</strong>
                </div>
              </div>
              <div class="col-6">
                <div class="bg-light rounded p-2 text-center">
                  <small class="text-muted d-block">Наработка</small>
                  <strong>{{ equipment.hours_worked }} ч</strong>
                </div>
              </div>
              <div class="col-6">
                <div class="bg-light rounded p-2 text-center">
                  <small class="text-muted d-block">Габариты</small>
                  <strong>{{ equipment.dimensions }}</strong>
                </div>
              </div>
              <div class="col-6">
                <div class="bg-light rounded p-2 text-center">
                  <small class="text-muted d-block">Локация</small>
                  <strong>{{ equipment.location }}</strong>
                </div>
              </div>
            </div>

            <!-- Календарь доступности -->
            <div class="card bg-light mb-3">
              <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-calendar-check me-2"></i>Календарь доступности</h6>
                <div class="mb-2">
                  <div class="d-flex gap-1 small mb-2">
                    <span class="badge bg-success">Свободно</span>
                    <span class="badge bg-danger">Занято</span>
                  </div>
                  <div class="row g-1">
                    <div v-for="(day, idx) in calendarDays" :key="idx" class="col" style="min-width:14%;">
                      <div class="text-center p-1 rounded" :class="dayClass(day)" @click="selectDay(day)">
                        <div class="small fw-bold">{{ day.dayOfWeek }}</div>
                        <div class="small">{{ day.dateNum }}</div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <button class="btn btn-sm btn-outline-secondary" @click="prevMonth"><i class="bi bi-chevron-left"></i></button>
                  <strong>{{ currentMonthName }} {{ currentYear }}</strong>
                  <button class="btn btn-sm btn-outline-secondary" @click="nextMonth"><i class="bi bi-chevron-right"></i></button>
                </div>
              </div>
            </div>

            <!-- Форма условий аренды -->
            <div class="card bg-light mb-3">
              <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-gear me-2"></i>Параметры аренды</h6>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Дата начала</label>
                    <input type="date" class="form-control" v-model="form.start_date" :min="minDate" @change="onDateChange">
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Дата окончания</label>
                    <input type="date" class="form-control" v-model="form.end_date" :min="form.start_date || minDate" @change="onDateChange">
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Смен в сутки</label>
                    <select class="form-select" v-model.number="form.shifts_per_day" @change="recalculatePrice">
                      <option :value="1">1 смена</option>
                      <option :value="2">2 смены</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <label class="form-label small fw-semibold">Часов в смене</label>
                    <select class="form-select" v-model.number="form.hours_per_shift" @change="recalculatePrice">
                      <option v-for="h in [1,2,3,4,5,6,7,8,9,10,11,12]" :key="h" :value="h">{{ h }} ч</option>
                    </select>
                  </div>
                </div>
                <div class="mb-2">
                  <label class="form-label small fw-semibold">Адрес доставки (опционально)</label>
                  <input type="text" class="form-control" v-model="form.address" placeholder="г. Москва, ул. Строителей, д. 10">
                </div>
              </div>
            </div>

            <!-- Расчёт стоимости (без комиссии) -->
            <div v-if="priceData" class="card border-primary mb-3">
              <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="small text-muted">{{ priceData.days }} дн. × {{ priceData.total_hours }} ч</div>
                  <div class="text-end">
                    <div class="small text-muted">Итого</div>
                    <div class="fs-4 fw-bold text-success">{{ priceData.total_final }} ₽</div>
                  </div>
                </div>
                <div v-if="!priceData.is_available" class="alert alert-warning mt-2 mb-0 py-1 small">
                  <i class="bi bi-exclamation-triangle me-1"></i> Техника недоступна на выбранные даты
                </div>
              </div>
            </div>

            <div v-if="error" class="alert alert-danger py-2 small">{{ error }}</div>

            <div class="d-grid gap-2">
              <button class="btn btn-primary btn-lg" @click="addToCart" :disabled="adding || !priceData?.is_available">
                <span v-if="adding" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-cart-plus me-1"></i>
                {{ adding ? 'Добавление...' : 'Добавить в корзину' }}
              </button>
              <a :href="'/catalog'" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Вернуться в каталог
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="equipment.description" class="card shadow-sm mt-4">
      <div class="card-header bg-white"><h5 class="mb-0">Описание</h5></div>
      <div class="card-body">
        <p class="mb-0">{{ equipment.description }}</p>
      </div>
    </div>

    <div v-if="equipment.specifications && equipment.specifications.length > 0" class="card shadow-sm mt-4">
      <div class="card-header bg-white"><h5 class="mb-0">Характеристики</h5></div>
      <div class="card-body">
        <table class="table table-sm">
          <tbody>
            <tr v-for="spec in equipment.specifications" :key="spec.key">
              <th class="text-muted" style="width:40%;">{{ spec.key }}</th>
              <td>{{ spec.value }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import '../eventBus.js';

const MONTHS = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
const DAYS_OF_WEEK = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];

export default {
  props: {
    equipmentId: { type: [Number, String], required: true },
  },
  data() {
    return {
      equipment: {},
      loading: true,
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
      // Календарь
      currentMonth: new Date().getMonth(),
      currentYear: new Date().getFullYear(),
      bookedDates: [],
      calendarDays: [],
    };
  },
  computed: {
    minDate() {
      const d = new Date();
      d.setDate(d.getDate() + 1);
      return d.toISOString().split('T')[0];
    },
    currentMonthName() {
      return MONTHS[this.currentMonth] || '';
    },
  },
  methods: {
    async loadEquipment() {
      try {
        const res = await fetch(`/api/equipment/${this.equipmentId}`);
        const data = await res.json();
        if (data.error) {
          this.error = data.error;
          return;
        }
        this.equipment = data;
        const tomorrow = data.default_start || this.minDate;
        const d = new Date(tomorrow);
        d.setDate(d.getDate() + 1);
        this.form.start_date = tomorrow;
        this.form.end_date = d.toISOString().split('T')[0];
        this.loadAvailability();
        this.recalculatePrice();
      } catch (e) {
        this.error = 'Ошибка загрузки данных';
      }
      this.loading = false;
    },
    async loadAvailability() {
      try {
        const res = await fetch(`/api/equipment/${this.equipmentId}/availability?month=${this.currentMonth + 1}&year=${this.currentYear}`);
        const data = await res.json();
        if (data.success) {
          this.bookedDates = data.booked_dates || [];
        } else {
          this.bookedDates = [];
        }
      } catch (e) {
        this.bookedDates = [];
      }
      this.buildCalendar();
    },
    buildCalendar() {
      const days = [];
      const firstDay = new Date(this.currentYear, this.currentMonth, 1);
      const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
      const startOffset = firstDay.getDay();

      // Пустые ячейки до первого дня
      for (let i = 0; i < startOffset; i++) {
        days.push({ empty: true });
      }

      for (let d = 1; d <= lastDay.getDate(); d++) {
        const date = new Date(this.currentYear, this.currentMonth, d);
        const dateStr = date.toISOString().split('T')[0];
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        days.push({
          empty: false,
          date: dateStr,
          dateNum: d,
          dayOfWeek: DAYS_OF_WEEK[date.getDay()],
          isBooked: this.bookedDates.includes(dateStr),
          isPast: date <= today,
          isToday: date.getTime() === today.getTime(),
        });
      }
      this.calendarDays = days;
    },
    dayClass(day) {
      if (day.empty) return '';
      if (day.isBooked || day.isPast) return 'bg-danger text-white';
      return 'bg-success text-white';
    },
    selectDay(day) {
      if (day.empty || day.isBooked || day.isPast) return;
      if (!this.form.start_date || (this.form.start_date && this.form.end_date)) {
        this.form.start_date = day.date;
        this.form.end_date = '';
      } else {
        if (day.date >= this.form.start_date) {
          this.form.end_date = day.date;
        } else {
          this.form.start_date = day.date;
        }
      }
      this.recalculatePrice();
    },
    onDateChange() {
      this.recalculatePrice();
    },
    prevMonth() {
      if (this.currentMonth === 0) {
        this.currentMonth = 11;
        this.currentYear--;
      } else {
        this.currentMonth--;
      }
      this.loadAvailability();
    },
    nextMonth() {
      if (this.currentMonth === 11) {
        this.currentMonth = 0;
        this.currentYear++;
      } else {
        this.currentMonth++;
      }
      this.loadAvailability();
    },
    async recalculatePrice() {
      if (!this.form.start_date || !this.form.end_date) return;
      try {
        const params = new URLSearchParams({
          start_date: this.form.start_date,
          end_date: this.form.end_date,
          shifts_per_day: this.form.shifts_per_day || 1,
          hours_per_shift: this.form.hours_per_shift || 8,
          quantity: this.form.quantity || 1,
        });
        const res = await fetch(`/api/equipment/${this.equipmentId}/price?` + params.toString());
        const data = await res.json();
        if (data.success) {
          this.priceData = data;
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
            equipment_id: this.equipmentId,
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
          if (window.cartBus) {
            window.cartBus.emit('cart-updated', data);
          }
          alert('Техника добавлена в корзину!');
        } else {
          this.error = data.error || 'Ошибка добавления в корзину';
        }
      } catch (e) {
        console.error('Add to cart error:', e);
        this.error = 'Ошибка соединения с сервером';
      }
      this.adding = false;
    },
  },
  mounted() {
    this.loadEquipment();
  },
};
</script>
