<template>
    <div class="sidebar-label" v-html="data.label"></div>
</template>

<script>
export default {
    props: ['data']
};
</script>

<style lang="scss" scoped>
.sidebar-label {
    font-size: .8em;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: .6;
    margin-top: 6px;
}
</style>
