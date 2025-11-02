<template>
    <div class="location-selector">
        <label class="form-label">Локация объекта *</label>

        <!-- Выбор существующей локации -->
        <div class="mb-3">
            <select class="form-select" v-model="selectedLocationId" @change="onLocationChange">
                <option value="">Выберите существующую локацию</option>
                <option v-for="location in existingLocations"
                        :key="location.id"
                        :value="location.id">
                    {{ location.name }} - {{ location.address }}
                </option>
                <option value="new">+ Добавить новую локацию</option>
            </select>
        </div>

        <!-- Форма для новой локации -->
        <div v-if="showNewLocationForm" class="card p-3 mb-3">
            <h6 class="mb-3">Добавить новую локацию</h6>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Название локации *</label>
                    <input type="text" class="form-control" v-model="newLocation.name"
                           placeholder="Например: Строительная площадка №1">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Адрес *</label>
                    <input type="text" class="form-control" v-model="newLocation.address"
                        placeholder="Начните вводить адрес">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Широта</label>
                    <input type="text" class="form-control" v-model="newLocation.latitude"
                           placeholder="55.7558">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Долгота</label>
                    <input type="text" class="form-control" v-model="newLocation.longitude"
                           placeholder="37.6173">
                </div>

                <div class="col-12">
                    <button type="button" class="btn btn-success btn-sm"
                            @click="saveNewLocation"
                            :disabled="!isNewLocationValid">
                        <i class="fas fa-save me-1"></i>Сохранить локацию
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2"
                            @click="cancelNewLocation">
                        Отмена
                    </button>
                </div>
            </div>
        </div>

        <!-- Выбранная локация -->
        <div v-if="selectedLocation" class="alert alert-info">
            <strong>Выбрана локация:</strong> {{ selectedLocation.name }}<br>
            <small>{{ selectedLocation.address }}</small>
        </div>
    </div>
</template>

<script>
export default {
    name: 'LocationSelector',
    props: {
        existingLocations: {
            type: Array,
            default: () => []
        },
        value: {
            type: Number,
            default: null
        }
    },
    data() {
        return {
            selectedLocationId: this.value,
            showNewLocationForm: false,
            newLocation: {
                name: '',
                address: '',
                latitude: '',
                longitude: ''
            },
            selectedLocation: null
        }
    },
    computed: {
        isNewLocationValid() {
            return this.newLocation.name.trim() && this.newLocation.address.trim();
        }
    },
    watch: {
        value(newVal) {
            this.selectedLocationId = newVal;
            this.updateSelectedLocation();
        }
    },
    methods: {
        initAddressAutocomplete() {
            console.log('Address autocomplete initialization');
            // Заглушка для будущей реализации
        },

        // ⚠️ ИСПРАВЛЕНИЕ: Обновленный метод onLocationChange с обработкой ошибок
        onLocationChange(event) {
            try {
                const selectedId = event.target.value;
                console.log('LocationSelector: selected ID:', selectedId);

                // ⚠️ ОБРАБОТАТЬ СЛУЧАЙ ПУСТОГО ВЫБОРА
                if (!selectedId) {
                    this.$emit('input', null);
                    this.$emit('location-selected', null);
                    this.selectedLocation = null;
                    return;
                }

                if (selectedId === 'new') {
                    this.showNewLocationForm = true;
                    this.selectedLocationId = null;
                    this.$emit('input', null);
                    this.$emit('location-selected', null);
                    this.selectedLocation = null;
                } else {
                    this.showNewLocationForm = false;
                    const locationId = parseInt(selectedId);
                    this.$emit('input', locationId);
                    this.updateSelectedLocation();

                    // Испускаем событие с полным объектом локации
                    if (this.selectedLocation) {
                        this.$emit('location-selected', this.selectedLocation);
                    } else {
                        // ⚠️ ОБРАБОТАТЬ СЛУЧАЙ НЕНАЙДЕННОЙ ЛОКАЦИИ
                        console.warn('Location not found for ID:', locationId);
                        this.$emit('location-selected', null);
                    }
                }
            } catch (error) {
                console.error('Error in LocationSelector:', error);
                // ⚠️ ОБЕСПЕЧИТЬ ОБРАБОТКУ ОШИБОК
                this.$emit('input', null);
                this.$emit('location-selected', null);
                this.selectedLocation = null;
            }
        },

       async saveNewLocation() {
        if (!this.isNewLocationValid) return;

        try {
            const response = await fetch('/lessee/locations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(this.newLocation)
            });

            const data = await response.json();

            if (data.success) {
                // Добавляем новую локацию в список
                this.$emit('location-created', data.location);

                // Выбираем новую локацию
                this.selectedLocationId = data.location.id;
                this.$emit('input', data.location.id); // Важно!

                // Сбрасываем форму
                this.cancelNewLocation();

                // Испускаем событие с объектом локации
                this.$emit('location-selected', data.location);

                console.log('New location created and selected:', data.location.id);
            } else {
                alert('Ошибка при создании локации: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Произошла ошибка при создании локации');
        }
    },

        cancelNewLocation() {
            this.showNewLocationForm = false;
            this.newLocation = {
                name: '',
                address: '',
                latitude: '',
                longitude: ''
            };
            this.selectedLocationId = null;
        },

        updateSelectedLocation() {
            if (this.selectedLocationId) {
                this.selectedLocation = this.existingLocations.find(
                    loc => loc.id === this.selectedLocationId
                );
            } else {
                this.selectedLocation = null;
            }
        }
    },
    mounted() {
        this.updateSelectedLocation();
        this.initAddressAutocomplete();
    }
}
</script>
