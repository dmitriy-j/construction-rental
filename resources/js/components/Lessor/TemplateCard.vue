<!-- resources/js/components/Lessor/TemplateCard.vue -->
<template>
  <div class="template-card card h-100">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <h6 class="card-title mb-0">{{ template.name }}</h6>
        <div class="form-check form-switch">
          <input
            class="form-check-input"
            type="checkbox"
            :checked="template.is_active"
            @change="toggleTemplate"
          >
        </div>
      </div>

      <p class="card-text text-muted small">{{ template.description }}</p>

      <div class="template-meta mb-3">
        <span class="badge bg-secondary me-2">{{ template.category?.name }}</span>
        <span class="badge bg-primary">
          <i class="fas fa-ruble-sign me-1"></i>
          {{ template.proposed_price }} / час
        </span>
      </div>

      <div class="template-stats mb-3">
        <div class="row text-center">
          <div class="col-4">
            <small class="text-muted">Применений</small>
            <div class="fw-bold">{{ template.usage_count }}</div>
          </div>
          <div class="col-4">
            <small class="text-muted">Успешность</small>
            <div class="fw-bold" :class="getSuccessRateClass(template.success_rate)">
              {{ template.success_rate }}%
            </div>
          </div>
          <div class="col-4">
            <small class="text-muted">Ответ</small>
            <div class="fw-bold">{{ template.response_time }}ч</div>
          </div>
        </div>
      </div>

      <div class="template-actions">
        <button
          class="btn btn-sm btn-outline-success me-2"
          @click="$emit('quick-apply', template)"
          title="Быстрое применение"
        >
          <i class="fas fa-bolt"></i>
        </button>
        <button
          class="btn btn-sm btn-outline-primary me-2"
          @click="$emit('apply', template)"
          title="Применить к заявке"
        >
          <i class="fas fa-paper-plane"></i>
        </button>
        <button
          class="btn btn-sm btn-outline-secondary me-2"
          @click="$emit('edit', template)"
          title="Редактировать"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button
          class="btn btn-sm btn-outline-danger"
          @click="$emit('delete', template)"
          title="Удалить"
        >
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TemplateCard',
  props: {
    template: {
      type: Object,
      required: true
    }
  },
  methods: {
    getSuccessRateClass(rate) {
      if (rate >= 70) return 'text-success';
      if (rate >= 40) return 'text-warning';
      return 'text-danger';
    },

    async toggleTemplate() {
      try {
        const response = await axios.put(`/api/lessor/proposal-templates/${this.template.id}`, {
          is_active: !this.template.is_active
        });

        this.$notify({
          title: 'Успех',
          text: `Шаблон ${this.template.is_active ? 'деактивирован' : 'активирован'}`,
          type: 'success'
        });

        this.$emit('updated');
      } catch (error) {
        console.error('Ошибка переключения шаблона:', error);
        this.$notify({
          title: 'Ошибка',
          text: 'Не удалось изменить статус шаблона',
          type: 'error'
        });
      }
    }
  }
}
</script>

<style scoped>
.template-card {
  transition: all 0.3s ease;
}

.template-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.template-actions .btn {
  padding: 0.25rem 0.5rem;
}

.card-title {
  color: #2c3e50;
  font-weight: 600;
}
</style>
