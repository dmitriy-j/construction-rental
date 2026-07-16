// Каталог техники — Vue 3 компонент
export default {
    data() {
        return {
            equipment: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            filters: { category: '', location: '', min_price: null, max_price: null, search: '' },
            sort: 'newest',
            perPage: 12,
            loading: false,
            filterOptions: { categories: [], locations: [] },
            searchTimer: null,
            autocompleteResults: [],
        };
    },
    computed: {
        total() { return this.meta.total; },
        pages() {
            let p = [], start = Math.max(1, this.meta.current_page - 2);
            let end = Math.min(this.meta.last_page, start + 4);
            for (let i = start; i <= end; i++) p.push(i);
            return p;
        }
    },
    methods: {
        async loadEquipment() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.meta.current_page,
                per_page: this.perPage, sort: this.sort,
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
            } catch (e) { console.error('Catalog load error:', e); }
            this.loading = false;
        },
        changePage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.meta.current_page = page;
            this.loadEquipment();
        },
        onSearchInput() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                if (this.filters.search.length > 2) {
                    fetch('/api/equipment/autocomplete?autocomplete=1&search=' + encodeURIComponent(this.filters.search))
                        .then(r => r.json()).then(d => this.autocompleteResults = d).catch(() => {});
                } else {
                    this.autocompleteResults = [];
                    this.loadEquipment();
                }
            }, 300);
        },
        resetFilters() {
            this.filters = { category: '', location: '', min_price: null, max_price: null, search: '' };
            this.sort = 'newest';
            this.meta.current_page = 1;
            this.loadEquipment();
        },
        viewDetails(id) { window.location.href = '/catalog/' + id; },
    },
    mounted() { this.loadEquipment(); },
    template: `
    <div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-xl-2 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-funnel text-primary"></i> Фильтры</h5>
                    <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">Сброс</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Поиск</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Название, бренд..." v-model="filters.search" @input="onSearchInput">
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
                        <label class="form-label fw-semibold small">Цена за час</label>
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
                            <img :src="eq.main_image_url || '/images/no-image.svg'" class="card-img-top" alt="" style="height:200px;object-fit:cover;">
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
                                    <button class="btn btn-primary btn-sm" @click="$emit('add-to-cart', eq)"><i class="bi bi-cart-plus"></i> В корзину</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            <div v-if="!loading && equipment.length === 0" class="text-center py-5">
                <i class="bi bi-box-seam display-1 text-muted"></i><p class="mt-3 text-muted">Техника не найдена</p>
            </div>
            <nav v-if="meta.last_page > 1" class="mt-4 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Страница {{ meta.current_page }} из {{ meta.last_page }}</span>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: meta.current_page <= 1 }"><button class="page-link" @click="changePage(meta.current_page - 1)">&laquo;</button></li>
                    <li class="page-item" v-for="p in pages" :key="p" :class="{ active: p === meta.current_page }"><button class="page-link" @click="changePage(p)">{{ p }}</button></li>
                    <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }"><button class="page-link" @click="changePage(meta.current_page + 1)">&raquo;</button></li>
                </ul>
            </nav>
        </div>
    </div>
    </div>
    `
};
