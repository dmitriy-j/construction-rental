<template>
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Отмена заявки</h5>
                    <button type="button" class="btn-close" @click="$emit('closed')"></button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите отменить заявку? Это действие нельзя будет отменить.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$emit('closed')">Отмена</button>
                    <button type="button" class="btn btn-danger" @click="$emit('confirmed')">Отменить заявку</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CancelRequestModal',
    props: {
        requestId: {
            type: [String, Number],
            required: true
        }
    },
    emits: ['confirmed', 'closed']
}
</script>
