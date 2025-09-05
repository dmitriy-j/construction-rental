<template>
    <div class="document-editor">
        <div class="row">
            <div class="col-md-8">
                <div id="luckysheet" style="width:100%; height:600px"></div>
            </div>
            <div class="col-md-4">
                <div class="field-mapping">
                    <h4>Настройка полей</h4>
                    <div v-for="(field, index) in mapping" :key="index" class="mb-3">
                        <label>Поле данных</label>
                        <input v-model="field.dataField" class="form-control">

                        <label>Ячейка в шаблоне</label>
                        <input v-model="field.cell" class="form-control">

                        <button @click="removeField(index)" class="btn btn-danger btn-sm">
                            Удалить
                        </button>
                    </div>
                    <button @click="addField" class="btn btn-primary">Добавить поле</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            mapping: [],
            luckySheet: null
        }
    },
    mounted() {
        this.initLuckysheet();
    },
    methods: {
        initLuckysheet() {
            // Инициализация Luckysheet
            this.luckySheet = luckysheet.create({
                container: 'luckysheet',
                title: 'Шаблон документа',
                lang: 'ru',
                plugins: ['chart'],
                showinfobar: false
            });
        },
        addField() {
            this.mapping.push({ dataField: '', cell: '' });
        },
        removeField(index) {
            this.mapping.splice(index, 1);
        },
        getMappingConfig() {
            return JSON.stringify(this.mapping);
        }
    }
}
</script>
