// Компонент оформления заказа — многошаговая форма
export default {
    data() {
        return {
            step: 1,
            orderType: 'regular', // regular | proposal
            deliveryAddress: '',
            comment: '',
            cartItems: [],
            proposalItems: [],
            total: 0,
            loading: false,
            success: false,
            orderId: null,
            error: null,
        };
    },
    computed: {
        isRegular() { return this.orderType === 'regular'; },
        isProposal() { return this.orderType === 'proposal'; },
        formatTotal() {
            return Number(this.total || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    },
    methods: {
        async loadCart() {
            try {
                const res = await fetch('/api/cart');
                const data = await res.json();
                this.cartItems = (data.items || []).map(item => {
                    const base = parseFloat(item.base_price) || 0;
                    const fee = parseFloat(item.platform_fee) || 0;
                    const period = parseInt(item.period_count) || 1;
                    const qty = parseInt(item.quantity) || 1;
                    item.total_price = parseFloat(item.total_price) || ((base + fee) * period * qty);
                    return item;
                });
                this.total = data.total || this.cartItems.reduce((sum, i) => sum + (i.total_price || 0), 0);
            } catch(e) { console.error(e); }
            try {
                const res2 = await fetch('/api/proposal-cart');
                const data2 = await res2.json();
                this.proposalItems = (data2.items || []).map(item => {
                    item.total_price = parseFloat(item.total_price) || 0;
                    return item;
                });
            } catch(e) {}
        },
        formatPrice(val) {
            return Number(val || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        nextStep() { this.step++; },
        prevStep() { this.step--; },
        switchType(type) {
            this.orderType = type;
            if (type === 'proposal') {
                this.total = this.proposalItems.reduce((sum, i) => sum + (i.total_price || 0), 0);
            } else {
                this.total = this.cartItems.reduce((sum, i) => sum + (i.total_price || 0), 0);
            }
        },
        async submitOrder() {
            this.loading = true;
            this.error = null;
            try {
                const endpoint = this.isRegular ? '/api/orders' : '/api/orders/proposal';
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                    body: JSON.stringify({ delivery_address: this.deliveryAddress, comment: this.comment }),
                });
                const data = await res.json();
                if (data.success || data.order_id) {
                    this.success = true;
                    this.orderId = data.order_id || data.message;
                    // Обновляем корзину через EventBus
                    if (window.cartBus) {
                        window.cartBus.emit('cart-updated', {});
                    }
                    setTimeout(() => { window.location.href = '/lessee/orders'; }, 2000);
                } else {
                    this.error = data.error || 'Ошибка создания заказа';
                }
            } catch(e) {
                this.error = 'Ошибка соединения';
            }
            this.loading = false;
        },
    },
    mounted() { this.loadCart(); },
    template: `
    <div class="container py-4">
        <h2 class="mb-4">Оформление заказа</h2>
        <div v-if="success" class="alert alert-success text-center py-5">
            <i class="bi bi-check-circle display-1 text-success"></i>
            <h3 class="mt-3">Заказ создан!</h3>
            <p v-if="orderId">Номер заказа: <strong>{{ orderId }}</strong></p>
            <p>Перенаправление в личный кабинет...</p>
        </div>
        <div v-else class="row">
            <div class="col-lg-8">
                <!-- Шаг 1: Выбор типа заказа -->
                <div v-if="step === 1" class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">1. Выберите тип заказа</h5></div>
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <div class="form-check card p-3 flex-grow-1" :class="{ 'border-primary': isRegular }">
                                <input type="radio" id="typeRegular" v-model="orderType" value="regular" class="form-check-input" @change="switchType('regular')">
                                <label for="typeRegular" class="form-check-label w-100">
                                    <strong>Из каталога</strong><br>
                                    <small class="text-muted">{{ cartItems.length }} позиций — {{ formatPrice(cartItems.reduce((s,i) => s + (i.total_price||0), 0)) }} ₽</small>
                                </label>
                            </div>
                            <div class="form-check card p-3 flex-grow-1" :class="{ 'border-primary': isProposal }">
                                <input type="radio" id="typeProposal" v-model="orderType" value="proposal" class="form-check-input" @change="switchType('proposal')">
                                <label for="typeProposal" class="form-check-label w-100">
                                    <strong>Из заявок</strong><br>
                                    <small class="text-muted">{{ proposalItems.length }} предложений — {{ formatPrice(proposalItems.reduce((s,i) => s + (i.total_price||0), 0)) }} ₽</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Шаг 2: Адрес -->
                <div v-if="step === 2" class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">2. Адрес доставки</h5></div>
                    <div class="card-body">
                        <input type="text" class="form-control" placeholder="Введите адрес доставки" v-model="deliveryAddress">
                    </div>
                </div>
                <!-- Шаг 3: Комментарий -->
                <div v-if="step === 3" class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">3. Комментарий</h5></div>
                    <div class="card-body">
                        <textarea class="form-control" rows="3" placeholder="Дополнительная информация" v-model="comment"></textarea>
                    </div>
                </div>
                <!-- Шаг 4: Подтверждение -->
                <div v-if="step === 4" class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><h5 class="mb-0">4. Подтверждение</h5></div>
                    <div class="card-body">
                        <p><strong>Тип заказа:</strong> {{ isRegular ? 'Из каталога' : 'Из заявок' }}</p>
                        <p><strong>Адрес доставки:</strong> {{ deliveryAddress || 'Не указан' }}</p>
                        <p><strong>Итого:</strong> <span class="fs-4 fw-bold text-primary">{{ formatPrice(total) }} ₽</span></p>
                        <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    </div>
                </div>
                <!-- Навигация -->
                <div class="d-flex justify-content-between">
                    <button v-if="step > 1" class="btn btn-outline-secondary" @click="prevStep">Назад</button>
                    <button v-if="step < 4" class="btn btn-primary ms-auto" @click="nextStep">Далее</button>
                    <button v-if="step === 4" class="btn btn-success ms-auto" @click="submitOrder" :disabled="loading">
                        {{ loading ? 'Оформление...' : 'Оформить заказ' }}
                    </button>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0">Состав заказа</h5></div>
                    <div class="card-body small">
                        <div v-for="item in cartItems" :key="item.id" class="mb-2 pb-2 border-bottom">
                            <strong>{{ item.equipment?.title || item.equipment?.brand + ' ' + item.equipment?.model || 'Техника' }}</strong><br>
                            <span v-if="item.start_date">{{ item.start_date }} — {{ item.end_date }}</span><br>
                            <span class="fw-bold text-primary">{{ formatPrice(item.total_price) }} ₽</span>
                        </div>
                        <div v-if="cartItems.length === 0 && proposalItems.length === 0" class="text-muted text-center py-3">
                            Корзина пуста
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>`
};
