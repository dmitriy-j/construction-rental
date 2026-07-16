// Корзина — компоненты CartIcon, CartPanel
import '../eventBus.js';

export const CartIcon = {
    data() {
        return { count: 0, items: [], proposalItems: [], total: 0 };
    },
    methods: {
        async loadCart() {
            try {
                const res = await fetch('/api/cart');
                const data = await res.json();
                this.count = data.count || 0;
                this.items = (data.items || []).map(item => {
                    const base = parseFloat(item.base_price) || 0;
                    const fee = parseFloat(item.platform_fee) || 0;
                    const period = parseInt(item.period_count) || 1;
                    const qty = parseInt(item.quantity) || 1;
                    item.total_price = parseFloat(item.total_price) || ((base + fee) * period * qty);
                    return item;
                });
                this.total = data.total || this.items.reduce((s, i) => s + (i.total_price || 0), 0);
                const totalDelivery = this.items.reduce((s, i) => s + (parseFloat(i.delivery_cost) || 0), 0);
                this.total += totalDelivery;
                try {
                    const res2 = await fetch('/api/proposal-cart');
                    const data2 = await res2.json();
                    this.proposalItems = (data2.items || []).map(item => {
                        item.total_price = parseFloat(item.total_price) || 0;
                        return item;
                    });
                    this.count += data2.count || 0;
                } catch(e) {}
            } catch(e) { console.error(e); }
        },
        openPanel() {
            document.getElementById('cartPanel').classList.add('show');
            document.body.classList.add('modal-open');
        },
        closePanel() {
            document.getElementById('cartPanel').classList.remove('show');
            document.body.classList.remove('modal-open');
        },
        removeItem(id) {
            fetch('/api/cart/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': window.csrfToken } })
                .then(r => r.json()).then(d => { if (d.success) this.loadCart(); });
        },
        removeProposalItem(id) {
            fetch('/api/proposal-cart/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': window.csrfToken } })
                .then(r => r.json()).then(d => { if (d.success) this.loadCart(); });
        },
        cleanupBrokenItems() {
            if (!confirm('Удалить ВСЕ позиции из корзины?')) return;
            // Удаляем каждую позицию по одной через API
            const promises = this.items.map(item =>
                fetch('/api/cart/' + item.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': window.csrfToken } })
            );
            Promise.all(promises).then(() => {
                alert('Корзина очищена');
                this.loadCart();
            });
        },
        formatPrice(val) {
            return Number(val || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    },
    mounted() {
        this.loadCart();
        if (window.cartBus) {
            window.cartBus.on('cart-updated', () => { this.loadCart(); });
        }
        document.addEventListener('cart-refresh', () => { this.loadCart(); });
    },
    template: `
    <div>
        <button class="btn btn-primary rounded-circle position-fixed shadow cart-icon" @click="openPanel" style="bottom:20px;right:20px;width:60px;height:60px;z-index:9999;">
            <i class="bi bi-cart fs-4"></i>
            <span v-if="count > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ count }}</span>
        </button>
        <div class="offcanvas offcanvas-end" id="cartPanel" tabindex="-1" style="z-index:10060;">
            <div class="offcanvas-header">
                <h5>Корзина <small v-if="total > 0" class="text-muted fs-6">({{ formatPrice(total) }} ₽)</small></h5>
                <button type="button" class="btn-close" @click="closePanel"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#cartItems">Техника</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proposalItems">Заявки</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="cartItems">
                        <div v-for="item in items" :key="item.id" class="card mb-2">
                            <div class="card-body p-2">
                                <div class="d-flex gap-2">
                                    <img :src="item.equipment?.main_image_url || (item.equipment?.mainImage?.path ? '/storage/' + item.equipment.mainImage.path : '/images/no-image.svg')" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                    <div class="flex-grow-1 small">
                                        <strong>{{ item.equipment?.title || item.equipment?.brand + ' ' + item.equipment?.model || 'Техника' }}</strong><br>
                                        <span v-if="item.start_date">{{ item.start_date }} — {{ item.end_date }}</span><br>
                                        <span v-if="item.hours_per_shift">{{ item.shifts_per_day }} см. × {{ item.hours_per_shift }} ч</span>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="text-muted">{{ formatPrice(item.base_price) }} ₽/ч</span>
                                            <span class="fw-bold text-primary">{{ formatPrice(item.total_price) }} ₽</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger" @click="removeItem(item.id)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <div v-if="items.length === 0" class="text-muted small text-center py-3">Корзина пуста</div>
                        <div v-if="items.length > 0">
                            <div v-for="item in items" :key="item.id">
                                <div v-if="item.delivery_cost > 0 && item.address && item.address.trim() !== ''" class="small border-top pt-1 mt-1" :title="''">
                                    <div class="small text-muted">
                                        <i class="bi bi-truck me-1"></i><strong>Доставка:</strong>
                                        <span class="fw-bold text-primary ms-1">{{ formatPrice(item.delivery_cost) }} ₽</span>
                                    </div>
                                    <div v-if="item.equipment?.location_name" class="small text-muted ms-1" :title="''">
                                        📍 Откуда: {{ item.equipment.location_name }}
                                    </div>
                                    <div v-if="item.address" class="small text-muted ms-1" :title="''">
                                        📍 Куда: {{ item.address }}
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-outline-danger btn-sm w-100 mt-2" @click="cleanupBrokenItems">
                                <i class="bi bi-trash3 me-1"></i>Очистить корзину полностью
                            </button>
                            <a href="/checkout" class="btn btn-primary w-100 mt-2">Перейти к оформлению</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proposalItems">
                        <div v-for="item in proposalItems" :key="item.id" class="card mb-2">
                            <div class="card-body p-2 small">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ item.equipment?.title || 'Предложение' }}</strong><br>
                                        Цена: <span class="text-primary fw-bold">{{ formatPrice(item.total_price) }} ₽</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger" @click="removeProposalItem(item.id)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <div v-if="proposalItems.length === 0" class="text-muted small text-center py-3">Нет предложений</div>
                        <a v-if="proposalItems.length > 0" href="/checkout" class="btn btn-primary w-100 mt-3">Перейти к оформлению</a>
                    </div>
                </div>
            </div>
        </div>
    </div>`
};
