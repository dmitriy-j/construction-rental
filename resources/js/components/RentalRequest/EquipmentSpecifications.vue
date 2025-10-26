<template>
  <div class="equipment-specifications">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light border-bottom-0 d-flex justify-content-between align-items-center">
        <div>
          <h6 class="card-title mb-0 text-primary">
            <i class="fas fa-sliders-h me-2"></i>Технические параметры
          </h6>
          <small class="text-muted">
            Укажите характеристики для точного подбора техники
          </small>
        </div>

        <button type="button" class="btn btn-sm btn-outline-primary"
                @click="toggleCustomParameters"
                :disabled="loading">
          <i class="fas" :class="showCustomParameters ? 'fa-eye-slash' : 'fa-plus'"></i>
          {{ showCustomParameters ? 'Скрыть' : 'Добавить свой параметр' }}
        </button>
      </div>

      <div class="card-body">
        <!-- Блок подсказки -->
        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" v-if="showHint">
          <div class="d-flex">
            <div class="flex-shrink-0">
              <i class="fas fa-info-circle fa-lg mt-1"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="alert-heading">Как работать с техническими параметрами?</h6>
              <ul class="mb-2 small">
                <li><strong>Стандартные параметры</strong> - отображаются автоматически для выбранной категории</li>
                <li><strong>Свои параметры</strong> - добавляйте любые характеристики через кнопку "Добавить свой параметр"</li>
                <li>Параметры не обязательны для заполнения</li>
                <li>Заполняйте только те характеристики, которые важны для вашего проекта</li>
              </ul>
              <button type="button" class="btn btn-sm btn-outline-info" @click="showHint = false">
                Понятно
              </button>
            </div>
          </div>
        </div>

        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
          </div>
          <p class="text-muted mt-2">Загружаем параметры...</p>
        </div>

        <!-- Основные параметры -->
        <div v-else-if="hasParameters" class="parameters-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-primary mb-0">
              <i class="fas fa-list-alt me-2"></i>
              Основные параметры
              <span class="badge bg-primary ms-2">{{ parameterTemplate.length }}</span>
            </h6>
            <small class="text-muted">
              Заполните нужные поля
            </small>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-6" v-for="param in parameterTemplate" :key="param.key">
              <div class="parameter-item card border-light">
                <div class="card-body py-3">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    <i class="fas fa-tag me-1 text-muted"></i>
                    {{ getRussianLabel(param.label, param.key) }}
                    <span v-if="param.unit" class="text-muted">({{ param.unit }})</span>
                  </label>
                  <input :type="param.type || 'text'"
                         class="form-control form-control-sm"
                         v-model="specifications[param.key]"
                         :placeholder="getPlaceholder(param)"
                         @input="onSpecificationsChange">
                  <small class="text-muted" v-if="param.default && !specifications[param.key]">
                    По умолчанию: {{ param.default }} {{ param.unit || '' }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Сообщение когда параметров нет -->
        <div v-else-if="!showCustomParameters && !loading" class="text-center py-4">
          <div class="empty-state">
            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
            <p class="text-muted mb-2">Для выбранной категории не заданы параметры</p>
            <p class="text-muted small">
              Вы можете добавить свои параметры или продолжить без указания характеристик
            </p>
          </div>
        </div>

        <!-- Свои параметры -->
        <div v-if="showCustomParameters" class="custom-parameters mt-4">
          <div class="border-top pt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="text-primary mb-0">
                <i class="fas fa-cogs me-2"></i>Свои параметры
                <span class="badge bg-success ms-2">{{ Object.keys(customParameters).length }}</span>
              </h6>
              <small class="text-muted">
                Добавьте любые дополнительные характеристики
              </small>
            </div>

            <!-- Сообщение если нет кастомных параметров -->
            <div v-if="Object.keys(customParameters).length === 0" class="text-center py-3">
              <p class="text-muted">Нет добавленных параметров</p>
            </div>

            <div v-for="(value, key) in customParameters" :key="key"
                 class="custom-parameter-item card border-success mb-3">
              <div class="card-body">
                <div class="row g-2 align-items-center">
                  <div class="col-md-5">
                    <label class="form-label small mb-1 text-success">
                      <i class="fas fa-pencil-alt me-1"></i>Название параметра
                    </label>
                    <input type="text"
                            class="form-control form-control-sm"
                            v-model="customParameterLabels[key]"
                            placeholder="Введите название параметра"
                            @input="onCustomParameterChange">
                  </div>
                  <div class="col-md-5">
                    <label class="form-label small mb-1 text-success">Значение</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           v-model="customParameters[key]"
                           placeholder="Введите значение"
                           @input="onCustomParameterChange">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small mb-1">&nbsp;</label>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger w-100"
                            @click="removeCustomParameter(key)"
                            title="Удалить параметр">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <button type="button"
                    class="btn btn-success btn-sm"
                    @click="addCustomParameter">
              <i class="fas fa-plus me-1"></i>Добавить параметр
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
    name: 'EquipmentSpecifications',
    props: {
        categoryId: {
            type: [String, Number],
            default: null
        },
        modelValue: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {
            parameterTemplate: [],
            specifications: {},
            customParameters: {},
            customParameterLabels: {},
            showCustomParameters: false,
            loading: false,
            showHint: true
        }
    },
    computed: {
        hasParameters() {
            return Array.isArray(this.parameterTemplate) && this.parameterTemplate.length > 0;
        }
    },

    watch: {
        categoryId: {
            immediate: true,
            handler(newCategoryId) {
                if (newCategoryId) {
                    this.loadParameterTemplate(newCategoryId);
                } else {
                    this.parameterTemplate = [];
                    this.specifications = {};
                }
            }
        },
        modelValue: {
            immediate: true,
            handler(newValue) {
                console.log('🔄 EquipmentSpecifications: получены новые данные', newValue);
                this.separateCustomParameters(newValue);
            },
            deep: true
        }
    },
    methods: {
        getRussianLabel(label, key) {
            const translations = {
                'bucket_volume': 'Объем ковша',
                'engine_power': 'Мощность двигателя',
                'operating_weight': 'Рабочий вес',
                'max_digging_depth': 'Максимальная глубина копания',
                'blade_width': 'Ширина отвала',
                'blade_height': 'Высота отвала',
                'load_capacity': 'Грузоподъемность',
                'body_volume': 'Объем кузова',
                'max_speed': 'Максимальная скорость',
                'Bucket volume': 'Объем ковша',
                'Engine power': 'Мощность двигателя',
                'Operating weight': 'Рабочий вес',
                'Max digging depth': 'Максимальная глубина копания',
                'Blade width': 'Ширина отвала',
                'Blade height': 'Высота отвала',
                'Load capacity': 'Грузоподъемность',
                'Body volume': 'Объем кузова',
                'Max speed': 'Максимальная скорость'
            };

            return translations[label] || translations[key] || label;
        },

        getPlaceholder(param) {
            if (param.placeholder) return param.placeholder;
            if (param.unit) return `Введите значение в ${param.unit}`;
            return 'Введите значение';
        },

        toggleCustomParameters() {
            this.showCustomParameters = !this.showCustomParameters;
            // НЕ добавляем автоматически параметры при показе блока
            console.log('🔧 Блок кастомных параметров:', this.showCustomParameters ? 'показан' : 'скрыт');
        },

        async loadParameterTemplate(categoryId) {
            this.loading = true;

            try {
                const response = await fetch(`/api/lessee/categories/${categoryId}/specifications`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();

                    let template = [];
                    if (data.success && data.template) {
                        template = Array.isArray(data.template) ? data.template : [];
                    }

                    this.parameterTemplate = template;
                    console.log('📋 Загружено стандартных параметров:', template.length);

                    this.initializeDefaultValues();
                } else {
                    this.parameterTemplate = [];
                }
            } catch (error) {
                console.error('Error loading parameter template:', error);
                this.parameterTemplate = [];
            } finally {
                this.loading = false;
            }
        },

        initializeDefaultValues() {
            if (!Array.isArray(this.parameterTemplate)) {
                this.parameterTemplate = [];
                return;
            }

            // Только инициализируем значения по умолчанию, если их нет
            this.parameterTemplate.forEach(param => {
                if (param.default !== undefined && !this.specifications[param.key]) {
                    this.specifications[param.key] = param.default;
                }
            });

            this.onSpecificationsChange();
        },

        separateCustomParameters(allSpecifications) {
            if (!allSpecifications || Object.keys(allSpecifications).length === 0) {
                console.log('🔄 Нет спецификаций для обработки');
                this.specifications = {};
                this.customParameters = {};
                this.customParameterLabels = {};
                return;
            }

            console.log('🔄 Обработка спецификаций:', allSpecifications);

            // Сбрасываем текущие данные
            this.specifications = {};
            this.customParameters = {};
            this.customParameterLabels = {};

            const templateKeys = this.parameterTemplate.map(param => param.key);
            console.log('📋 Ключи шаблона:', templateKeys);

            // Обрабатываем новый формат с metadata
            if (allSpecifications.values && allSpecifications.labels) {
                console.log('🔧 Используем новый формат данных (values + labels)');

                Object.keys(allSpecifications.values).forEach(key => {
                    const value = allSpecifications.values[key];

                    if (templateKeys.includes(key)) {
                        // Это стандартный параметр
                        this.specifications[key] = value;
                        console.log(`✅ Стандартный параметр: ${key} = ${value}`);
                    } else {
                        // Это кастомный параметр
                        this.customParameters[key] = value;
                        this.customParameterLabels[key] = allSpecifications.labels[key] || '';
                        console.log(`✅ Кастомный параметр: ${key} = ${value}, label: ${this.customParameterLabels[key]}`);
                    }
                });
            }
            // Обрабатываем старый формат
            else {
                console.log('🔧 Используем старый формат данных');

                Object.keys(allSpecifications).forEach(key => {
                    const value = allSpecifications[key];

                    if (templateKeys.includes(key)) {
                        // Это стандартный параметр
                        this.specifications[key] = value;
                        console.log(`✅ Стандартный параметр: ${key} = ${value}`);
                    } else if (key !== 'labels') {
                        // Это кастомный параметр
                        this.customParameters[key] = value;
                        this.customParameterLabels[key] = ''; // Пустая метка
                        console.log(`✅ Кастомный параметр: ${key} = ${value}`);
                    }
                });
            }

            // Показываем блок кастомных параметров, если они есть
            if (Object.keys(this.customParameters).length > 0) {
                this.showCustomParameters = true;
            }

            console.log('📊 Итоговые данные:', {
                specifications: this.specifications,
                customParameters: this.customParameters,
                customParameterLabels: this.customParameterLabels
            });
        },

        onSpecificationsChange() {
            console.log('🔄 Изменены стандартные параметры');
            this.emitAllSpecifications();
        },

        onCustomParameterChange() {
            console.log('🔄 Изменены кастомные параметры');
            this.emitAllSpecifications();
        },

        emitAllSpecifications() {
            const allSpecifications = {
                ...this.specifications,
                ...this.customParameters
            };

            const specificationsWithMetadata = {
                values: allSpecifications,
                labels: { ...this.customParameterLabels }
            };

            console.log('📤 Отправка обновленных спецификаций:', specificationsWithMetadata);
            this.$emit('update:modelValue', specificationsWithMetadata);
        },

        addCustomParameter() {
            const newKey = `custom_${Date.now()}`;
            this.customParameters[newKey] = '';
            this.customParameterLabels[newKey] = '';

            console.log('➕ Добавлен один кастомный параметр:', newKey);

            // Показываем блок кастомных параметров
            this.showCustomParameters = true;

            this.onCustomParameterChange();

            // Фокус на новое поле названия параметра
            this.$nextTick(() => {
                const newItem = this.$el.querySelector('.custom-parameter-item:last-child input');
                if (newItem) {
                    newItem.focus();
                }
            });
        },

        removeCustomParameter(key) {
            if (confirm('Удалить этот параметр?')) {
                console.log('➖ Удален кастомный параметр:', key);
                delete this.customParameters[key];
                delete this.customParameterLabels[key];
                this.onCustomParameterChange();

                // Скрываем блок кастомных параметров, если их не осталось
                if (Object.keys(this.customParameters).length === 0) {
                    this.showCustomParameters = false;
                }
            }
        }
    }
}
</script>

<style scoped>
.parameters-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
  padding: 1rem;
  border-radius: 0.5rem;
}

.parameter-item {
  transition: all 0.3s ease;
  border-left: 3px solid #0d6efd !important;
}

.parameter-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.custom-parameter-item {
  border-left: 3px solid #20c997 !important;
  background: linear-gradient(135deg, #f8fff8 0%, #f0fff0 100%);
}

.empty-state {
  padding: 2rem;
  background: #f8f9fa;
  border-radius: 0.5rem;
  border: 2px dashed #dee2e6;
}

.alert-info {
  border-left: 4px solid #0dcaf0;
}

.card-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
}
</style>
