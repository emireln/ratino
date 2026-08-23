<template>
    <select v-model="value">
        <option v-for="option in data.options" :key="option.value" :value="option.value" v-html="option.text"></option>
    </select>
</template>

<script>
export default {
    props: ['data'],
    data() {
        return { value: this.data.value ?? this.data.options[0].value };
    },
    watch: {
        value() {
            if (this.data.onSelect) this.data.onSelect(this.value);
        }
    }
};
</script>
