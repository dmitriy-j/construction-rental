<template>
    <div class="virtual-list" :style="{ height: containerHeight + 'px' }" @scroll="handleScroll">
        <div class="virtual-list-container" :style="{ height: totalHeight + 'px' }">
            <div v-for="visibleItem in visibleItems"
                 :key="visibleItem.originalIndex"
                 class="virtual-item"
                 :style="{ transform: `translateY(${visibleItem.offset}px)` }">
                <slot :item="visibleItem.data" :index="visibleItem.originalIndex"/>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        items: Array,
        itemHeight: {
            type: Number,
            default: 100
        },
        containerHeight: {
            type: Number,
            default: 400
        }
    },
    data() {
        return {
            scrollTop: 0
        }
    },
    computed: {
        totalHeight() {
            return this.items.length * this.itemHeight;
        },
        visibleItems() {
            const startIndex = Math.floor(this.scrollTop / this.itemHeight);
            const endIndex = Math.min(
                startIndex + Math.ceil(this.containerHeight / this.itemHeight) + 1,
                this.items.length
            );

            const visible = [];
            for (let i = startIndex; i < endIndex; i++) {
                visible.push({
                    data: this.items[i],
                    originalIndex: i,
                    offset: i * this.itemHeight
                });
            }
            return visible;
        }
    },
    methods: {
        handleScroll(event) {
            this.scrollTop = event.target.scrollTop;
        }
    }
}
</script>

<style scoped>
.virtual-list {
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.virtual-list-container {
    position: relative;
}

.virtual-item {
    position: absolute;
    width: 100%;
    will-change: transform;
    contain: content;
}
</style>
