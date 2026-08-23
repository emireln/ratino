<template>
    <div class="sidebar-custom-history">
        <div class="history-scroll">
            <div v-for="(entry, i) in entries" :key="i" class="history-entry">
                <span v-html="entry.text"></span>
            </div>
        </div>
    </div>
</template>

<script>
import bus from '../../../lib/bus';

export default {
    data() {
        return { entries: [] };
    },
    mounted() {
        this.unsub = bus.on('game:customHistory:add', (e) => {
            this.entries.unshift(e);
            if (this.entries.length > 50) this.entries.pop();
        });
    },
    beforeUnmount() {
        if (typeof this.unsub === 'function') this.unsub();
    }
};
</script>

<style lang="scss" scoped>
.history-scroll {
    max-height: 260px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.history-entry {
    padding: 6px 10px;
    border-radius: 4px;
    font-size: .8em;
    background: rgba(255, 255, 255, .04);

    .color {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 6px;
    }
}
</style>
