<template>
    <span>{{ computedValue }}</span>
</template>

<script>
import { useAppStore } from '../../stores/app';

export default {
    props: ['value', 'to'],
    computed: {
        computedValue() {
            const unit = useAppStore().unit;
            const value = Number(this.value || 0);
            switch (unit) {
                case 'k': return Math.abs(value) > 999 ? Math.sign(value) * (Math.abs(value) / 1000).toFixed(1) + ' K' : value.toFixed(2);
                case 'm': return Math.abs(value) > 999999 ? Math.sign(value) * (Math.abs(value) / 1000000).toFixed(1) + ' M' : value.toFixed(2);
                case 'b': return Math.abs(value) > 999999999 ? Math.sign(value) * (Math.abs(value) / 1000000000).toFixed(1) + ' B' : value.toFixed(2);
                default: return value.toFixed(2);
            }
        }
    }
};
</script>
