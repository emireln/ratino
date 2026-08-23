<template>
    <div>
        <input :value="value" @input="onInput" :placeholder="data.placeholder" :min="data.min" :max="data.max">
    </div>
</template>

<script>
export default {
    props: ['data'],
    data() {
        return { value: this.data.value ?? '' };
    },
    methods: {
        onInput(e) {
            const v = e.target.value;
            this.value = v;
            if (this.data.callback) this.data.callback(v);
        }
    }
};
</script>
