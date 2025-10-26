<template>
    <!-- Модальное окно для отправки предложения -->
    <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Отправить предложение</h5>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>
                <div class="modal-body">
                    <div v-if="request" class="request-info mb-3 p-3 bg-light rounded">
                        <h6>Заявка: {{ request.title }}</h6>
                        <p class="mb-1">Период: {{ formatDate(request.rental_period.start) }} - {{ formatDate(request.rental_period.end) }}</p>
                    </div>

                    <!-- Форма предложения -->
                    <form @submit.prevent="submitProposal">
                        <div class="mb-3">
                            <label class="form-label">Выберите оборудование *</label>
                            <select v-model="form.equipment_id" class="form-select" required>
                                <option value="">Выберите оборудование</option>
                                <option v-for="equipment in myEquipment" :key="equipment.id" :value="equipment.id">
                                    {{ equipment.title }} ({{ equipment.brand }} {{ equipment.model }})
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Предлагаемая цена (₽/час) *</label>
                            <input type="number" v-model.number="form.proposed_price" class="form-control" min="0" step="50" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Количество *</label>
                            <input type="number" v-model.number="form.proposed_quantity" class="form-control" min="1" :max="request.total_quantity" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Сообщение арендатору *</label>
                            <textarea v-model="form.message" class="form-control" rows="4" placeholder="Опишите ваше предложение..." required></textarea>
                        </div>

                        <!-- Расчет наценки -->
                        <div v-if="calculatedPrice" class="price-calculation p-3 bg-light rounded mb-3">
                            <h6>Расчет стоимости:</h6>
                            <div class="d-flex justify-content-between">
                                <span>Ваша цена:</span>
                                <span>{{ formatCurrency(form.proposed_price) }}/час</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Наценка платформы:</span>
                                <span>+ {{ formatCurrency(calculatedPrice.platform_markup.total) }}</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Итог для арендатора:</span>
                                <span class="text-success">{{ formatCurrency(calculatedPrice.final_price) }}/час</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" @click="$emit('close')">Отмена</button>
                    <button type="submit" class="btn btn-primary" :disabled="submitting" @click="submitProposal">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                        Отправить предложение
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Фон модального окна -->
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>

<script>
export default {
    name: 'ProposalModal',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        request: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            submitting: false,
            myEquipment: [],
            form: {
                equipment_id: '',
                proposed_price: 0,
                proposed_quantity: 1,
                message: ''
            }
        }
    },
    computed: {
        calculatedPrice() {
            if (!this.form.proposed_price || !this.request) return null;

            // Формула комбинированной наценки: 100₽ + (Экономия × 30%)
            const clientSaving = Math.max(0, (this.request.max_hourly_rate || this.request.hourly_rate) - this.form.proposed_price);
            const fixedMarkup = 100;
            const percentageMarkup = clientSaving * 0.3;
            const totalMarkup = fixedMarkup + percentageMarkup;
            const finalPrice = this.form.proposed_price + totalMarkup;

            return {
                client_saving: clientSaving,
                platform_markup: {
                    fixed: fixedMarkup,
                    percentage: percentageMarkup,
                    total: totalMarkup
                },
                final_price: finalPrice
            };
        }
    },
    methods: {
        async loadMyEquipment() {
            try {
                // Загрузка оборудования арендодателя
                const response = await fetch('/api/lessor/equipment/my');
                const data = await response.json();
                if (data.success) {
                    this.myEquipment = data.data;
                }
            } catch (error) {
                console.error('Ошибка загрузки оборудования:', error);
            }
        },
        async submitProposal() {
            this.submitting = true;
            try {
                const response = await fetch(`/api/rental-requests/${this.request.id}/proposals`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (data.success) {
                    this.$emit('proposal-created', data.data);
                    this.$emit('close');
                    // Можно показать уведомление об успехе
                    alert('Предложение успешно отправлено!');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Ошибка отправки предложения:', error);
                alert('Ошибка: ' + error.message);
            } finally {
                this.submitting = false;
            }
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        },
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU');
        }
    },
    watch: {
        show: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.loadMyEquipment();
                    // Сброс формы при открытии модального окна
                    this.form = {
                        equipment_id: '',
                        proposed_price: this.request?.hourly_rate || 0,
                        proposed_quantity: 1,
                        message: ''
                    };
                }
            }
        }
    }
}
</script>

<style scoped>
.modal-backdrop {
    opacity: 0.5;
}
.price-calculation {
    border-left: 4px solid #0d6efd;
}
</style>
