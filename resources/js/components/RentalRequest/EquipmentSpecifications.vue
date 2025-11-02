<template>
  <div class="equipment-specifications">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light border-bottom-0 d-flex justify-content-between align-items-center">
        <div>
          <h6 class="card-title mb-0 text-primary">
            <i class="fas fa-sliders-h me-2"></i>Технические параметры
          </h6>
        </div>

        <button type="button" class="btn btn-sm btn-outline-primary"
                @click="toggleCustomParameters">
          <i class="fas" :class="showCustomParameters ? 'fa-eye-slash' : 'fa-plus'"></i>
          {{ showCustomParameters ? 'Скрыть' : 'Добавить свой параметр' }}
        </button>
      </div>

      <div class="card-body">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
          </div>
        </div>

        <!-- Основные параметры -->
        <div v-else-if="hasParameters" class="parameters-section">
          <div class="row g-3">
            <div class="col-12 col-md-6" v-for="param in parameterTemplate" :key="param.key">
              <div class="parameter-item card border-light">
                <div class="card-body py-3">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    {{ getRussianLabel(param.label, param.key) }}
                    <span v-if="param.unit" class="text-muted">({{ param.unit }})</span>
                  </label>
                  <input :type="param.type || 'text'"
                         class="form-control form-control-sm"
                         :value="specifications[param.key]"
                         @input="updateStandardParameter(param.key, $event.target.value)"
                         :placeholder="getPlaceholder(param)">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Свои параметры -->
        <div v-if="showCustomParameters" class="custom-parameters mt-4">
          <div class="border-top pt-4">
            <div v-if="customParams.length === 0" class="text-center py-3">
              <p class="text-muted">Нет добавленных параметров</p>
            </div>

            <div v-for="(param, index) in customParams" :key="param.id"
                 class="custom-parameter-item card border-success mb-3">
              <div class="card-body">
                <div class="row g-3 align-items-center">
                  <!-- Название параметра -->
                  <div class="col-md-4">
                    <label class="form-label small mb-1 text-success">Название параметра</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           v-model="param.name"
                           placeholder="Например: Колесная формула">
                  </div>

                  <!-- Значение -->
                  <div class="col-md-4">
                    <label class="form-label small mb-1 text-success">Значение</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           v-model="param.value"
                           placeholder="Например: 6x6, полный привод">
                  </div>

                  <!-- Единица измерения -->
                  <div class="col-md-3">
                    <label class="form-label small mb-1 text-success">Единица измерения</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           v-model="param.unit"
                           placeholder="шт, м, кг...">
                  </div>

                  <!-- Удаление -->
                  <div class="col-md-1">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger w-100"
                            @click="removeCustomParameter(index)"
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
            customParams: [],
            showCustomParameters: false,
            loading: false
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
                }
            }
        },
        modelValue: {
            immediate: true,
            handler(newValue) {
                this.separateCustomParameters(newValue);
            },
            deep: true
        }
    },
    methods: {
        updateStandardParameter(key, value) {
            this.specifications[key] = value === '' ? null : value;
            this.emitUpdate();
        },

        emitUpdate() {
            const customValues = {};
            const customMetadata = {};

            this.customParams.forEach(param => {
                const key = param.key || `custom_${param.id}`;
                customValues[key] = param.value || null;
                customMetadata[key] = {
                    name: param.name || '',
                    unit: param.unit || ''
                };
            });

            const allSpecifications = {
                values: {
                    ...this.specifications,
                    ...customValues
                },
                metadata: customMetadata
            };

            this.$emit('update:modelValue', allSpecifications);
        },

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
                'max_speed': 'Максимальная скорость'
            };
            return translations[key] || label;
        },

        getPlaceholder(param) {
            if (param.unit) return `Введите значение в ${param.unit}`;
            return 'Введите значение';
        },

        toggleCustomParameters() {
            this.showCustomParameters = !this.showCustomParameters;
        },

        async loadParameterTemplate(categoryId) {
            this.loading = true;
            try {
                const response = await fetch(`/api/lessee/categories/${categoryId}/specifications`);
                if (response.ok) {
                    const data = await response.json();
                    this.parameterTemplate = data.success && data.template ? data.template : [];
                }
            } catch (error) {
                this.parameterTemplate = [];
            } finally {
                this.loading = false;
            }
        },

        separateCustomParameters(allSpecifications) {
            if (!allSpecifications) return;

            this.specifications = {};
            this.customParams = [];

            const templateKeys = this.parameterTemplate.map(param => param.key);

            Object.keys(allSpecifications).forEach(key => {
                if (key === 'labels' || key === 'metadata') return;

                if (templateKeys.includes(key)) {
                    this.specifications[key] = allSpecifications[key];
                } else if (key.startsWith('custom_')) {
                    const metadata = allSpecifications.metadata?.[key] || {};
                    this.customParams.push({
                        id: key,
                        key: key,
                        name: metadata.name || '',
                        value: allSpecifications[key],
                        unit: metadata.unit || ''
                    });
                }
            });

            if (allSpecifications.values) {
                Object.keys(allSpecifications.values).forEach(key => {
                    if (templateKeys.includes(key)) {
                        this.specifications[key] = allSpecifications.values[key];
                    } else if (key.startsWith('custom_')) {
                        const existing = this.customParams.find(p => p.key === key);
                        const metadata = allSpecifications.metadata?.[key] || {};
                        if (!existing) {
                            this.customParams.push({
                                id: key,
                                key: key,
                                name: metadata.name || '',
                                value: allSpecifications.values[key],
                                unit: metadata.unit || ''
                            });
                        }
                    }
                });
            }

            if (this.customParams.length > 0) {
                this.showCustomParameters = true;
            }
        },

        addCustomParameter() {
            this.customParams.push({
                id: Date.now(),
                key: `custom_${Date.now()}`,
                name: '',
                value: '',
                unit: ''
            });
        },

        removeCustomParameter(index) {
            if (confirm('Удалить этот параметр?')) {
                this.customParams.splice(index, 1);
                this.emitUpdate();
            }
        }
    }
}
</script>

<style scoped>
.parameter-item {
  border-left: 3px solid #0d6efd !important;
}

.custom-parameter-item {
  border-left: 3px solid #20c997 !important;
}
</style>
