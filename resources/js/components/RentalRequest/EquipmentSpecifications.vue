<template>
    <div class="equipment-specifications">
        <div class="specifications-section">
            <!-- Стандартные спецификации -->
            <div v-if="standardSpecs.length > 0" class="standard-specs mb-4">
                <h6 class="specs-title">Стандартные параметры</h6>
                <div class="row g-3">
                    <div v-for="spec in standardSpecs" :key="spec.key" class="col-md-6">
                        <label class="form-label">{{ spec.label }}</label>

                        <!-- ОСОБАЯ ОБРАБОТКА ДЛЯ ОБЪЕМА КОВША -->
                        <input
                            v-if="spec.key === 'bucket_volume'"
                            type="number"
                            class="form-control"
                            :placeholder="spec.placeholder"
                            v-model="standardValues[spec.key]"
                            :step="spec.validation?.step || '0.1'"
                            :min="spec.validation?.min || '0.1'"
                            :max="spec.validation?.max || '20'"
                            @input="onBucketVolumeChange($event.target.value, spec.key)"
                        >

                        <!-- ОБЫЧНЫЕ ЧИСЛОВЫЕ ПОЛЯ -->
                        <input
                            v-else-if="spec.type === 'number'"
                            type="number"
                            class="form-control"
                            :placeholder="spec.placeholder"
                            v-model="standardValues[spec.key]"
                            @input="onSpecificationChange"
                        >

                        <!-- ТЕКСТОВЫЕ ПОЛЯ -->
                        <input
                            v-else
                            type="text"
                            class="form-control"
                            :placeholder="spec.placeholder"
                            v-model="standardValues[spec.key]"
                            @input="onSpecificationChange"
                        >

                        <small v-if="spec.unit" class="form-text text-muted">
                            Единица измерения: {{ spec.unit }}
                        </small>

                        <!-- СПЕЦИАЛЬНОЕ СООБЩЕНИЕ ДЛЯ ОБЪЕМА КОВША -->
                        <small v-if="spec.key === 'bucket_volume'" class="form-text text-info">
                            ⚠️ Стандартные объемы: 0.8, 1.0, 1.2, 1.5, 2.0 м³
                        </small>
                    </div>
                </div>
            </div>

            <!-- Кастомные спецификации -->
            <div class="custom-specs">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="specs-title mb-0">Дополнительные параметры</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addCustomSpec">
                        <i class="fas fa-plus me-1"></i>Добавить параметр
                    </button>
                </div>

                <div v-for="(spec, index) in customSpecs" :key="spec.id" class="custom-spec-item card mb-3">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Название параметра *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="spec.label"
                                    placeholder="Например: Количество осей"
                                    @input="onCustomSpecChange(index)"
                                    required
                                >
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Значение *</label>
                                <input
                                    :type="spec.dataType === 'number' ? 'number' : 'text'"
                                    class="form-control"
                                    v-model="spec.value"
                                    @input="onCustomSpecChange(index)"
                                    required
                                >
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Единица измерения</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="spec.unit"
                                    placeholder="шт, кг, м"
                                    @input="onCustomSpecChange(index)"
                                >
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Тип данных</label>
                                <select class="form-select" v-model="spec.dataType" @change="onCustomSpecChange(index)">
                                    <option value="string">Текст</option>
                                    <option value="number">Число</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button
                                    type="button"
                                    class="btn btn-danger w-100"
                                    @click="removeCustomSpec(index)"
                                    title="Удалить параметр"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="customSpecs.length === 0" class="text-center py-4 text-muted">
                    <i class="fas fa-list-alt fa-2x mb-2"></i>
                    <p>Нет дополнительных параметров</p>
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
            required: true
        },
        modelValue: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {
            standardSpecs: [],
            standardValues: {},
            customSpecs: [],
            isLoading: false,
            preventCategoryReload: false,
            isEmittingUpdate: false,
            lastEmittedData: null,
            isInitializing: false,
            isExternalUpdate: false,
            preventReinitialization: false,
            debounceTimer: null
        }
    },
    computed: {
        currentSpecifications() {
            return {
                standard_specifications: { ...this.standardValues },
                custom_specifications: this.prepareCustomSpecificationsForEmit()
            };
        }
    },
    watch: {
        categoryId: {
            immediate: true,
            handler(newCategoryId) {
                if (newCategoryId && !this.preventCategoryReload) {
                    console.log('🔄 EquipmentSpecifications: загрузка спецификаций для категории', newCategoryId);
                    this.loadCategorySpecifications();
                } else if (!newCategoryId) {
                    console.log('🔄 EquipmentSpecifications: сброс спецификаций (нет категории)');
                    this.standardSpecs = [];
                    this.customSpecs = [];
                    this.standardValues = {};
                }
            }
        },
        modelValue: {
            deep: true,
            handler(newValue, oldValue) {
                if (this.isEmittingUpdate) {
                    console.log('🛑 EquipmentSpecifications: предотвращена циклическая переинициализация (isEmittingUpdate)');
                    return;
                }

                const newValueStr = JSON.stringify(newValue);
                const oldValueStr = JSON.stringify(oldValue);
                const lastEmittedStr = JSON.stringify(this.lastEmittedData);

                if (newValueStr === oldValueStr) {
                    console.log('🛑 EquipmentSpecifications: данные не изменились, пропускаем переинициализацию');
                    return;
                }

                if (newValueStr === lastEmittedStr) {
                    console.log('🛑 EquipmentSpecifications: получены наши же данные, пропускаем переинициализацию');
                    return;
                }

                console.log('🔄 EquipmentSpecifications: modelValue изменен (внешнее обновление)', {
                    стандартные_новые: Object.keys(newValue?.standard_specifications || {}).length,
                    кастомные_новые: Object.keys(newValue?.custom_specifications || {}).length,
                    стандартные_старые: Object.keys(oldValue?.standard_specifications || {}).length,
                    кастомные_старые: Object.keys(oldValue?.custom_specifications || {}).length
                });

                this.initializeFromModelValue(newValue);
            }
        }
    },
    methods: {
        // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Подготовка кастомных спецификаций для эмита
        prepareCustomSpecificationsForEmit() {
            const customSpecs = {};

            this.customSpecs.forEach((spec, index) => {
                // ✅ ИЗМЕНЕНИЕ: Отправляем даже если value пустое, но label заполнен
                if (spec.label && spec.label.trim()) {
                    const key = spec.id || `custom_${Date.now()}_${index}`;

                    // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Гарантируем что unit всегда строка, НИКОГДА не null
                    let unitValue = '';
                    if (spec.unit !== null && spec.unit !== undefined && spec.unit !== '') {
                        unitValue = String(spec.unit);
                    }
                    // Если spec.unit равен null, undefined или пустой строке - unitValue останется пустой строкой

                    // ✅ ДЕТАЛЬНАЯ ОТЛАДКА
                    console.log('🔍 Подготовка кастомной спецификации для эмита:', {
                        key,
                        label: spec.label,
                        value: spec.value,
                        originalUnit: spec.unit,
                        normalizedUnit: unitValue,
                        unitType: typeof unitValue,
                        isNull: unitValue === null
                    });

                    const preparedSpec = {
                        label: String(spec.label || ''),
                        value: spec.dataType === 'number' ?
                              (spec.value === '' ? null : Number(spec.value)) :
                              String(spec.value || ''),
                        unit: unitValue, // ✅ Гарантируем строку, не null
                        dataType: String(spec.dataType || 'string')
                    };

                    // ✅ ФИНАЛЬНАЯ ПРОВЕРКА - unit НИКОГДА не должен быть null
                    if (preparedSpec.unit === null) {
                        console.error('❌ КРИТИЧЕСКАЯ ОШИБКА: unit всё равно null после нормализации!');
                        preparedSpec.unit = '';
                    }

                    customSpecs[key] = preparedSpec;
                }
            });

            console.log('📦 Подготовлены кастомные спецификации для эмита:', {
                количество: Object.keys(customSpecs).length,
                ключи: Object.keys(customSpecs),
                данные: customSpecs,
                units: Object.values(customSpecs).map(s => ({ unit: s.unit, type: typeof s.unit }))
            });

            return customSpecs;
        },

        // ✅ ДОБАВЛЕН НОВЫЙ МЕТОД: Дополнительная защита от null в unit
        ensureUnitIsString(specs) {
            const cleanedSpecs = {};

            Object.keys(specs).forEach(key => {
                const spec = specs[key];
                if (spec && typeof spec === 'object') {
                    cleanedSpecs[key] = {
                        ...spec,
                        unit: spec.unit !== null && spec.unit !== undefined ? String(spec.unit) : ''
                    };
                }
            });

            return cleanedSpecs;
        },

        async loadCategorySpecifications() {
            this.isLoading = true;
            try {
                console.log('🔧 EquipmentSpecifications: загрузка шаблона для категории', this.categoryId);

                if (this.categoryId && this.isExcavatorCategory(this.categoryId)) {
                    console.log('🏗️ Обнаружена категория экскаватора, используем фиксированные спецификации');
                    this.standardSpecs = this.getExcavatorSpecifications();
                    this.initializeStandardValues();
                    this.validateSpecifications();
                    return;
                }

                const response = await fetch(`/api/specifications/template/${this.categoryId}`);
                const data = await response.json();

                if (data.success) {
                    this.standardSpecs = data.data.standard_specifications || [];
                    console.log('✅ EquipmentSpecifications: загружено стандартных спецификаций', this.standardSpecs.length);
                    this.initializeStandardValues();
                } else {
                    console.error('❌ EquipmentSpecifications: API вернул ошибку:', data.message);
                    this.standardSpecs = this.getFallbackSpecifications();
                    this.initializeStandardValues();
                }
            } catch (error) {
                console.error('❌ EquipmentSpecifications: ошибка загрузки спецификаций:', error);
                this.standardSpecs = this.getFallbackSpecifications();
                this.initializeStandardValues();
            } finally {
                this.isLoading = false;
                this.validateSpecifications();
            }
        },

        isExcavatorCategory(categoryId) {
            const excavatorIds = [1, 2, 3, 4, 5];
            return excavatorIds.includes(Number(categoryId));
        },

        getExcavatorSpecifications() {
            console.log('🏗️ Загрузка спецификаций для экскаватора');
            return [
                {
                    'key': 'bucket_volume',
                    'label': 'Объем ковша',
                    'unit': 'м³',
                    'type': 'number',
                    'placeholder': '1.5',
                    'validation': {
                        'min': 0.1,
                        'max': 20,
                        'step': 0.1
                    }
                },
                {
                    'key': 'weight',
                    'label': 'Вес',
                    'unit': 'т',
                    'type': 'number',
                    'placeholder': 'Введите значение в т'
                },
                {
                    'key': 'power',
                    'label': 'Мощность',
                    'unit': 'л.с.',
                    'type': 'number',
                    'placeholder': 'Введите значение в л.с.'
                },
                {
                    'key': 'max_digging_depth',
                    'label': 'Макс. глубина копания',
                    'unit': 'м',
                    'type': 'number',
                    'placeholder': 'Введите значение в м'
                },
                {
                    'key': 'engine_power',
                    'label': 'Мощность двигателя',
                    'unit': 'кВт',
                    'type': 'number',
                    'placeholder': 'Введите значение в кВт'
                }
            ];
        },

        getFallbackSpecifications() {
            console.log('🔄 EquipmentSpecifications: использование fallback спецификаций');
            return [
                {
                    'key': 'weight',
                    'label': 'Вес',
                    'unit': 'т',
                    'type': 'number',
                    'placeholder': 'Введите значение в т'
                },
                {
                    'key': 'power',
                    'label': 'Мощность',
                    'unit': 'л.с.',
                    'type': 'number',
                    'placeholder': 'Введите значение в л.с.'
                }
            ];
        },

        initializeStandardValues() {
            this.standardValues = {};
            this.standardSpecs.forEach(spec => {
                let initialValue = this.modelValue.standard_specifications?.[spec.key] || '';

                if (spec.key === 'bucket_volume' && initialValue) {
                    initialValue = parseFloat(initialValue);
                    if (isNaN(initialValue)) {
                        initialValue = '';
                    }
                }

                this.standardValues[spec.key] = initialValue;
            });
            console.log('✅ EquipmentSpecifications: инициализированы стандартные значения', this.standardValues);
        },

        initializeFromModelValue(modelValue) {
            if (this.isEmittingUpdate || this.isInitializing) {
                console.log('🛑 EquipmentSpecifications: предотвращена циклическая инициализация');
                return;
            }

            this.isInitializing = true;
            console.log('🔄 EquipmentSpecifications: инициализация из modelValue', {
                has_standard: !!modelValue?.standard_specifications,
                has_custom: !!modelValue?.custom_specifications,
                standard_count: Object.keys(modelValue?.standard_specifications || {}).length,
                custom_count: Object.keys(modelValue?.custom_specifications || {}).length
            });

            try {
                if (modelValue?.standard_specifications) {
                    this.standardValues = { ...modelValue.standard_specifications };
                    console.log('✅ EquipmentSpecifications: стандартные значения установлены из modelValue',
                        Object.keys(this.standardValues).length);
                } else {
                    this.initializeStandardValues();
                }

                this.customSpecs = [];
                if (modelValue?.custom_specifications && Object.keys(modelValue.custom_specifications).length > 0) {
                    Object.entries(modelValue.custom_specifications).forEach(([key, spec]) => {
                        // ✅ ИСПРАВЛЕНИЕ: Нормализуем unit при инициализации
                        let normalizedUnit = '';
                        if (spec.unit !== null && spec.unit !== undefined) {
                            normalizedUnit = String(spec.unit);
                        }

                        this.customSpecs.push({
                            id: key,
                            label: spec.label || '',
                            value: spec.value || '',
                            unit: normalizedUnit,
                            dataType: spec.dataType || 'string'
                        });
                    });
                    console.log('✅ EquipmentSpecifications: кастомные спецификации восстановлены из modelValue',
                        this.customSpecs.length);
                } else {
                    console.log('✅ EquipmentSpecifications: кастомные спецификации инициализированы пустым массивом');
                }

                console.log('🎯 EquipmentSpecifications: инициализация завершена', {
                    стандартные: Object.keys(this.standardValues).length,
                    кастомные: this.customSpecs.length
                });

            } catch (error) {
                console.error('❌ EquipmentSpecifications: ошибка инициализации:', error);
            } finally {
                this.isInitializing = false;
            }
        },

        onBucketVolumeChange(value, key) {
            console.log('💧 Изменение объема ковша:', {
                значение: value,
                ключ: key,
                преобразованное: parseFloat(value)
            });

            if (value !== '' && value !== null) {
                const numericValue = parseFloat(value);
                if (!isNaN(numericValue)) {
                    this.standardValues[key] = numericValue;
                    console.log('✅ Объем ковша преобразован в число:', numericValue);
                }
            }

            this.debouncedEmitUpdate();
        },

        onSpecificationChange() {
            console.log('✏️ EquipmentSpecifications: изменены стандартные спецификации');
            this.debouncedEmitUpdate();
        },

        addCustomSpec() {
            const newSpec = {
                id: 'custom_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                label: '',
                value: '',
                unit: '', // ✅ Начинаем с пустой строки, а не null
                dataType: 'string'
            };
            this.customSpecs.push(newSpec);

            console.log('➕ EquipmentSpecifications: добавлена новая кастомная спецификация', {
                id: newSpec.id,
                всего_кастомных: this.customSpecs.length,
                список: this.customSpecs.map(s => ({ label: s.label, id: s.id }))
            });

            this.$nextTick(() => {
                this.emitUpdate();
            });
        },

        removeCustomSpec(index) {
            const removedSpec = this.customSpecs[index];
            console.log('➖ EquipmentSpecifications: удалена кастомная спецификация', {
                index,
                label: removedSpec?.label,
                id: removedSpec?.id,
                осталось: this.customSpecs.length - 1
            });

            this.customSpecs.splice(index, 1);
            this.emitUpdate();
        },

        onCustomSpecChange(index) {
            const spec = this.customSpecs[index];

            // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Гарантируем что unit всегда строка
            if (spec.unit === null || spec.unit === undefined) {
                spec.unit = '';
                console.log('🔄 EquipmentSpecifications: unit нормализован в пустую строку', {
                    index,
                    id: spec.id
                });
            }

            console.log('✏️ EquipmentSpecifications: изменена кастомная спецификация', {
                index,
                label: spec.label,
                value: spec.value,
                unit: spec.unit,
                unitType: typeof spec.unit,
                dataType: spec.dataType,
                id: spec.id,
                всего_кастомных: this.customSpecs.length
            });

            if (spec.dataType === 'number' && spec.value !== '') {
                const numValue = Number(spec.value);
                if (!isNaN(numValue)) {
                    spec.value = numValue;
                }
            }

            this.debouncedEmitUpdate();
        },

        debouncedEmitUpdate() {
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            this.debounceTimer = setTimeout(() => {
                this.emitUpdate();
            }, 500);
        },

        emitUpdate() {
            if (this.isInitializing) {
                console.log('🛑 EquipmentSpecifications: предотвращен эмит во время инициализации');
                return;
            }

            console.log('🔥 EquipmentSpecifications: EMIT данных спецификаций');

            this.isEmittingUpdate = true;

            try {
                let customSpecs = this.prepareCustomSpecificationsForEmit();

                // ✅ ДОПОЛНИТЕЛЬНАЯ ЗАЩИТА: Очищаем unit от null значений
                customSpecs = this.ensureUnitIsString(customSpecs);

                const unifiedSpecs = {
                    standard_specifications: { ...this.standardValues },
                    custom_specifications: customSpecs
                };

                // ✅ КРИТИЧЕСКАЯ ПРОВЕРКА: Проверяем что unit не null во всех спецификациях
                let hasNullUnit = false;
                Object.keys(unifiedSpecs.custom_specifications).forEach(key => {
                    const spec = unifiedSpecs.custom_specifications[key];
                    if (spec.unit === null) {
                        console.error(`❌ КРИТИЧЕСКАЯ ОШИБКА: unit всё равно null для ${key}`);
                        unifiedSpecs.custom_specifications[key].unit = '';
                        hasNullUnit = true;
                    }
                });

                if (hasNullUnit) {
                    console.error('🚨 ВНИМАНИЕ: Были обнаружены null значения unit, они были заменены на пустые строки');
                }

                this.lastEmittedData = JSON.parse(JSON.stringify(unifiedSpecs));

                console.log('📤 EquipmentSpecifications отправляет:', {
                    стандартные_ключи: Object.keys(unifiedSpecs.standard_specifications),
                    кастомные_ключи: Object.keys(unifiedSpecs.custom_specifications),
                    кастомные_количество: Object.keys(unifiedSpecs.custom_specifications).length,
                    кастомные_данные: unifiedSpecs.custom_specifications,
                    units_check: Object.values(unifiedSpecs.custom_specifications).map(s => ({
                        unit: s.unit,
                        type: typeof s.unit,
                        isNull: s.unit === null
                    }))
                });

                this.$emit('update:modelValue', unifiedSpecs);

            } catch (error) {
                console.error('❌ EquipmentSpecifications: ошибка при эмите:', error);
            } finally {
                setTimeout(() => {
                    this.isEmittingUpdate = false;
                }, 100);
            }
        },

        validateSpecifications() {
            console.log('🔍 ДИАГНОСТИКА ВАЛИДАЦИИ:', {
                стандартные_значения: this.standardValues,
                стандартные_спецификации: this.standardSpecs,
                кастомные_спецификации: this.customSpecs
            });

            const hasBucketVolume = this.standardSpecs.some(spec => spec.key === 'bucket_volume');
            console.log('📦 Есть ли поле bucket_volume:', hasBucketVolume);

            if (hasBucketVolume) {
                const bucketVolumeValue = this.standardValues.bucket_volume;
                console.log('💧 Значение bucket_volume:', {
                    значение: bucketVolumeValue,
                    тип: typeof bucketVolumeValue,
                    преобразованное: parseFloat(bucketVolumeValue),
                    isNaN: isNaN(parseFloat(bucketVolumeValue))
                });
            }
        },

        checkComponentState() {
            console.log('🔍 EquipmentSpecifications: ТЕКУЩЕЕ СОСТОЯНИЕ', {
                isEmittingUpdate: this.isEmittingUpdate,
                isInitializing: this.isInitializing,
                standardSpecsCount: this.standardSpecs.length,
                customSpecsCount: this.customSpecs.length,
                standardValuesCount: Object.keys(this.standardValues).length,
                lastEmittedData: this.lastEmittedData ? {
                    standard_count: Object.keys(this.lastEmittedData.standard_specifications || {}).length,
                    custom_count: Object.keys(this.lastEmittedData.custom_specifications || {}).length
                } : 'none'
            });
        }
    },
    mounted() {
        console.log('🔧 EquipmentSpecifications: компонент смонтирован', {
            categoryId: this.categoryId,
            начальные_данные: this.modelValue
        });
        this.initializeFromModelValue(this.modelValue);

        setTimeout(() => {
            this.validateSpecifications();
        }, 1000);
    },

    beforeUnmount() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        console.log('🔧 EquipmentSpecifications: компонент размонтируется, таймеры очищены');
    }
}
</script>

<style scoped>
.equipment-specifications {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    background: #f8f9fa;
}

.specs-title {
    color: #495057;
    font-weight: 600;
    border-bottom: 2px solid #0d6efd;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.custom-spec-item {
    border-left: 4px solid #20c997;
    transition: all 0.3s ease;
}

.custom-spec-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Специальные стили для поля объема ковша */
input[type="number"] {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}

input[type="number"]:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Адаптивность */
@media (max-width: 768px) {
    .equipment-specifications {
        padding: 1rem;
    }

    .custom-spec-item .card-body {
        padding: 1rem;
    }

    .custom-spec-item .row > [class*="col-"] {
        margin-bottom: 1rem;
    }

    .specs-title {
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .equipment-specifications {
        padding: 0.75rem;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>
