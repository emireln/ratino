<template>
    <div class="sidebar-buttons">
        <div v-for="(button, i) in data.buttons" :key="i"
            :class="['sidebar-button', active === i ? 'active' : '', button.class || '']"
            @click="onClick(button, i)">
            <span v-if="!button.hideLabel" v-html="button.label"></span>
        </div>
    </div>
</template>

<script>
export default {
    props: ['data'],
    data() {
        return { active: -1 };
    },
    methods: {
        onClick(button, i) {
            if (this.gameInstance.playTimeout || this.gameInstance.game.extendedState === 'in-progress') {
                if (button.isAction) button.callback();
                return;
            }
            this.active = i;
            if (button.callback) button.callback();
        }
    }
};
</script>

<style lang="scss" scoped>
.sidebar-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;

    .sidebar-button {
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: .9em;
        background: rgba(255, 255, 255, .06);

        &.active {
            background: #e51f49;
            color: #fff;
        }
    }
}
</style>
